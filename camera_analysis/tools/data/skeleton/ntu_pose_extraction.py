# Copyright (c) VideoAnalysis. All rights reserved.
import abc
import argparse
import os.path as osp
import os
import torch
import torch.nn as nn
import mmengine
import cv2

from collections import defaultdict
from tempfile import TemporaryDirectory
from pathlib import Path
import numpy as np
from mmaction.utils import frame_extract
from mmdet.apis import inference_detector, init_detector
from mmdet.structures import DetDataSample
from mmpose.apis import inference_topdown, init_model
from mmpose.structures import PoseDataSample, merge_data_samples
import os.path as osp
from pathlib import Path
from typing import List, Union
from mmengine.structures import InstanceData
from mmengine.utils import track_iter_progress
from mmpose.visualization import PoseLocalVisualizer

class NTUPoseExtractor:
    def __init__(self, device='cuda:0', skip_postproc=False):
        self.device = device
        self.skip_postproc = skip_postproc
        self.det_config = 'demo/demo_configs/faster-rcnn_r50-caffe_fpn_ms-1x_coco-person.py'
        self.det_checkpoint = 'demo/faster_rcnn_r50_fpn_1x_coco-person_20201216_175929-d022e227.pth'
        self.det_score_thr = 0.5
        self.pose_config = 'demo/demo_configs/td-hm_hrnet-w32_8xb64-210e_coco-256x192_infer.py'
        self.pose_checkpoint = 'demo/hrnet_w32_coco_256x192-c78dce93_20200708.pth'
        self.pose_estimator = init_model(self.pose_config, self.pose_checkpoint, 'cuda:0')
        self.detector = init_detector(config=self.det_config, checkpoint=self.det_checkpoint, device='cuda:0')
        self.visualizer = PoseLocalVisualizer()
        


    @staticmethod
    def intersection(b0, b1):
        l, r = max(b0[0], b1[0]), min(b0[2], b1[2])
        u, d = max(b0[1], b1[1]), min(b0[3], b1[3])
        return max(0, r - l) * max(0, d - u)

    @staticmethod
    def iou(b0, b1):
        i = NTUPoseExtractor.intersection(b0, b1)
        u = NTUPoseExtractor.area(b0) + NTUPoseExtractor.area(b1) - i
        return i / u

    @staticmethod
    def area(b):
        return (b[2] - b[0]) * (b[3] - b[1])

    @staticmethod
    def removedup(bbox):
        def inside(box0, box1, threshold=0.8):
            return NTUPoseExtractor.intersection(box0, box1) / NTUPoseExtractor.area(box0) > threshold
        num_bboxes = bbox.shape[0]
        if num_bboxes == 1 or num_bboxes == 0:
            return bbox
        valid = []
        for i in range(num_bboxes):
            flag = True
            for j in range(num_bboxes):
                if i != j and inside(bbox[i], bbox[j]) and bbox[i][4] <= bbox[j][4]:
                    flag = False
                    break
            if flag:
                valid.append(i)
        return bbox[valid]

    @staticmethod
    def is_easy_example(det_results, num_person):
        threshold = 0.95
        def thre_bbox(bboxes, threshold=threshold):
            shape = [sum(bbox[:, -1] > threshold) for bbox in bboxes]
            ret = np.all(np.array(shape) == shape[0])
            return shape[0] if ret else -1
        if thre_bbox(det_results) == num_person:
            det_results = [x[x[..., -1] > 0.95] for x in det_results]
            return True, np.stack(det_results)
        return False, thre_bbox(det_results)

    @staticmethod
    def bbox2tracklet(bbox):
        iou_thre = 0.6
        tracklet_id = -1
        tracklet_st_frame = {}
        tracklets = defaultdict(list)
        for t, box in enumerate(bbox):
            for idx in range(box.shape[0]):
                matched = False
                for tlet_id in range(tracklet_id, -1, -1):
                    cond1 = NTUPoseExtractor.iou(tracklets[tlet_id][-1][-1], box[idx]) >= iou_thre
                    cond2 = (t - tracklet_st_frame[tlet_id] - len(tracklets[tlet_id]) < 10)
                    cond3 = tracklets[tlet_id][-1][0] != t
                    if cond1 and cond2 and cond3:
                        matched = True
                        tracklets[tlet_id].append((t, box[idx]))
                        break
                if not matched:
                    tracklet_id += 1
                    tracklet_st_frame[tracklet_id] = t
                    tracklets[tracklet_id].append((t, box[idx]))
        return tracklets

    @staticmethod
    def drop_tracklet(tracklet):
        tracklet = {k: v for k, v in tracklet.items() if len(v) > 5}
        def meanarea(track):
            boxes = np.stack([x[1] for x in track]).astype(np.float32)
            areas = (boxes[..., 2] - boxes[..., 0]) * (boxes[..., 3] - boxes[..., 1])
            return np.mean(areas)
        tracklet = {k: v for k, v in tracklet.items() if meanarea(v) > 5000}
        return tracklet

    @staticmethod
    def distance_tracklet(tracklet):
        dists = {}
        for k, v in tracklet.items():
            bboxes = np.stack([x[1] for x in v])
            c_x = (bboxes[..., 2] + bboxes[..., 0]) / 2.
            c_y = (bboxes[..., 3] + bboxes[..., 1]) / 2.
            c_x -= 480
            c_y -= 270
            c = np.concatenate([c_x[..., None], c_y[..., None]], axis=1)
            dist = np.linalg.norm(c, axis=1)
            dists[k] = np.mean(dist)
        return dists

    @staticmethod
    def tracklet2bbox(track, num_frame):
        bbox = np.zeros((num_frame, 5))
        trackd = {}
        for k, v in track:
            bbox[k] = v
            trackd[k] = v
        for i in range(num_frame):
            if bbox[i][-1] <= 0.5:
                mind = np.Inf
                for k in trackd:
                    if np.abs(k - i) < mind:
                        mind = np.abs(k - i)
                bbox[i] = bbox[k]
        return bbox

    @staticmethod
    def tracklets2bbox(tracklet, num_frame):
        dists = NTUPoseExtractor.distance_tracklet(tracklet)
        sorted_inds = sorted(dists, key=lambda x: dists[x])
        dist_thre = np.Inf
        for i in sorted_inds:
            if len(tracklet[i]) >= num_frame / 2:
                dist_thre = 2 * dists[i]
                break
        dist_thre = max(50, dist_thre)
        bbox = np.zeros((num_frame, 5))
        bboxd = {}
        for idx in sorted_inds:
            if dists[idx] < dist_thre:
                for k, v in tracklet[idx]:
                    if bbox[k][-1] < 0.01:
                        bbox[k] = v
                        bboxd[k] = v
        bad = 0
        for idx in range(num_frame):
            if bbox[idx][-1] < 0.01:
                bad += 1
                mind = np.Inf
                mink = None
                for k in bboxd:
                    if np.abs(k - idx) < mind:
                        mind = np.abs(k - idx)
                        mink = k
                bbox[idx] = bboxd[mink]
        return bad, bbox[:, None, :]

    @staticmethod
    def bboxes2bbox(bbox, num_frame):
        ret = np.zeros((num_frame, 2, 5))
        for t, item in enumerate(bbox):
            if item.shape[0] <= 2:
                ret[t, :item.shape[0]] = item
            else:
                inds = sorted(list(range(item.shape[0])), key=lambda x: -item[x, -1])
                ret[t] = item[inds[:2]]
        for t in range(num_frame):
            if ret[t, 0, -1] <= 0.01:
                ret[t] = ret[t - 1]
            elif ret[t, 1, -1] <= 0.01:
                if t:
                    if ret[t - 1, 0, -1] > 0.01 and ret[t - 1, 1, -1] > 0.01:
                        if NTUPoseExtractor.iou(ret[t, 0], ret[t - 1, 0]) > NTUPoseExtractor.iou(ret[t, 0], ret[t - 1, 1]):
                            ret[t, 1] = ret[t - 1, 1]
                        else:
                            ret[t, 1] = ret[t - 1, 0]
        return ret

    def detection_inference(self,
                        detection_model,
                        frame_paths: List[str],
                        det_score_thr: float = 0.9,
                        det_cat_id: int = 0,
                        device: Union[str, torch.device] = 'cuda:0',
                        with_score: bool = False) -> tuple:

        model = detection_model
        results = []
        data_samples = []
        print('Performing Human Detection for each frame')
        for frame_path in track_iter_progress(frame_paths):
            det_data_sample: DetDataSample = inference_detector(model, frame_path)
            pred_instance = det_data_sample.pred_instances.cpu().numpy()
            bboxes = pred_instance.bboxes
            scores = pred_instance.scores
            valid_idx = np.logical_and(pred_instance.labels == det_cat_id,
                                    pred_instance.scores > det_score_thr)
            bboxes = bboxes[valid_idx]
            scores = scores[valid_idx]

            if with_score:
                bboxes = np.concatenate((bboxes, scores[:, None]), axis=-1)
            results.append(bboxes)
            data_samples.append(det_data_sample)

        return results, data_samples


    def pose_inference(self,
                    pose_estimator,
                    frame_paths: List[str],
                    det_results: List[np.ndarray],
                    device: Union[str, torch.device] = 'cuda:0') -> tuple:
        
        model = pose_estimator
        results = []
        data_samples = []
        annotated_images = []
        print('Performing Human Pose Estimation for each frame')
        for f, d in track_iter_progress(list(zip(frame_paths, det_results))):
            pose_data_samples: List[PoseDataSample] \
                = inference_topdown(model, f, d[..., :4], bbox_format='xyxy')
            pose_data_sample = merge_data_samples(pose_data_samples)
            pose_data_sample.dataset_meta = model.dataset_meta
            # make fake pred_instances
            if not hasattr(pose_data_sample, 'pred_instances'):
                num_keypoints = model.dataset_meta['num_keypoints']
                pred_instances_data = dict(
                    keypoints=np.empty(shape=(0, num_keypoints, 2)),
                    keypoints_scores=np.empty(shape=(0, 17), dtype=np.float32),
                    bboxes=np.empty(shape=(0, 4), dtype=np.float32),
                    bbox_scores=np.empty(shape=(0), dtype=np.float32))
                pose_data_sample.pred_instances = InstanceData(
                    **pred_instances_data)

            poses = pose_data_sample.pred_instances.to_dict()
            results.append(poses)
            data_samples.append(pose_data_sample)
            annotated_image = self.annotate_image(f, pose_data_sample)
            annotated_images.append(annotated_image)

        return results, data_samples, annotated_images

    def ntu_det_postproc(self, vid, det_results):
        det_results = [self.removedup(x) for x in det_results]
        label = int(vid.split('/')[-1].split('A')[1][:3])
        mpaction = list(range(50, 61)) + list(range(106, 121))
        n_person = 2 if label in mpaction else 1
        is_easy, bboxes = self.is_easy_example(det_results, n_person)
        if is_easy:
            print('\nEasy Example')
            return bboxes
        tracklets = self.bbox2tracklet(det_results)
        tracklets = self.drop_tracklet(tracklets)
        print(f'\nHard {n_person}-person Example, found {len(tracklets)} tracklet')
        if n_person == 1:
            if len(tracklets) == 1:
                tracklet = list(tracklets.values())[0]
                det_results = self.tracklet2bbox(tracklet, len(det_results))
                return np.stack(det_results)
            else:
                bad, det_results = self.tracklets2bbox(tracklets, len(det_results))
                return det_results
        # n_person is 2
        if len(tracklets) <= 2:
            tracklets = list(tracklets.values())
            bboxes = []
            for tracklet in tracklets:
                bboxes.append(self.tracklet2bbox(tracklet, len(det_results))[:, None])
            bbox = np.concatenate(bboxes, axis=1)
            return bbox
        else:
            return self.bboxes2bbox(det_results, len(det_results))

    def pose_inference_with_align(self, frame_paths, det_results):
        det_results = [frm_dets for frm_dets in det_results if frm_dets.shape[0] > 0]
        pose_results, _, annotated_images = self.pose_inference(self.pose_estimator, frame_paths, det_results, self.device)
        num_persons = max([pose['keypoints'].shape[0] for pose in pose_results])
        num_points = pose_results[0]['keypoints'].shape[1]
        num_frames = len(pose_results)
        keypoints = np.zeros((num_persons, num_frames, num_points, 2), dtype=np.float32)
        scores = np.zeros((num_persons, num_frames, num_points), dtype=np.float32)
        for f_idx, frm_pose in enumerate(pose_results):
            frm_num_persons = frm_pose['keypoints'].shape[0]
            for p_idx in range(frm_num_persons):
                keypoints[p_idx, f_idx] = frm_pose['keypoints'][p_idx]
                scores[p_idx, f_idx] = frm_pose['keypoint_scores'][p_idx]
        return keypoints, scores, annotated_images

    def ntu_pose_extraction(self, vid):
        tmp_dir = TemporaryDirectory()
        frame_paths, _ = frame_extract(vid, out_dir=tmp_dir.name)
        det_results, _ = self.detection_inference(
            self.detector,
            frame_paths,
            self.det_score_thr,
            device=self.device,
            with_score=True)
        if not self.skip_postproc:
            det_results = self.ntu_det_postproc(vid, det_results)
        
        anno = dict()
        keypoints, scores, annotated_images = self.pose_inference_with_align(frame_paths, det_results)
        
        anno['keypoint'] = keypoints
        anno['keypoint_score'] = scores
        anno['frame_dir'] = osp.splitext(osp.basename(vid))[0]
        anno['img_shape'] = (1080, 1920)
        anno['original_shape'] = (1080, 1920)
        anno['total_frames'] = keypoints.shape[1]
        anno['label'] = int(osp.basename(vid).split('A')[1][:3]) - 1
        tmp_dir.cleanup()
        return anno, annotated_images
    
    def annotate_image(self, image_path, data_sample):
        image = cv2.imread(image_path)
        if image is None:
            raise ValueError(f"Failed to read image: {image}")
        self.visualizer.set_dataset_meta(self.pose_estimator.dataset_meta)
        self.visualizer.add_datasample(
            'result',
            image,
            data_sample=data_sample,
            draw_gt=False,
            draw_bbox=True,
            draw_heatmap=False,
            show_kpt_idx=True,
            skeleton_style='mmpose',
            show=False
        )
        annotated_image = self.visualizer.get_image()
        return annotated_image

    def process_video_folder(self, input_folder, output_folder):
        os.makedirs(output_folder, exist_ok=True)
        video_extensions = ('.avi', '.mp4', '.mov', '.mkv')
        video_files = [str(f) for f in Path(input_folder).rglob("*") if f.suffix.lower() in video_extensions]
        if not video_files:
            raise ValueError(f"No video files found in {input_folder}")
        
        total_videos = len(video_files)
        print(f"Found {total_videos} videos to process")
        
        if video_files:
            video_path = video_files[0]
            video_name = Path(video_path).stem
            try:
                print(f"Processing {video_name} (1/{total_videos})")
                anno, annotated_images = self.ntu_pose_extraction(video_path)
                
                return {
                    'video_name': video_name,
                    'anno': anno,
                    'annotated_images': annotated_images,
                    'remaining_videos': video_files[1:],
                    'total_videos': total_videos
                }
            except Exception as e:
                print(f"Error processing {video_path}: {str(e)}")
                if len(video_files) > 1:
                    return self.process_next_video(video_files[1:], output_folder, total_videos)
                else:
                    raise ValueError("Failed to process any videos")
        else:
            return None, total_videos

    def process_next_video(self, remaining_videos, output_folder, total_videos):
        """Process the next video in the queue"""
        if not remaining_videos:
            return None, total_videos
            
        video_path = remaining_videos[0]
        video_name = Path(video_path).stem
        
        try:
            print(f"Processing {video_name} ({total_videos - len(remaining_videos) + 1}/{total_videos})")
            anno, annotated_images = self.ntu_pose_extraction(video_path)
            
            return {
                'video_name': video_name,
                'anno': anno,
                'annotated_images': annotated_images,
                'remaining_videos': remaining_videos[1:],
                'total_videos': total_videos
            }
        except Exception as e:
            print(f"Error processing {video_path}: {str(e)}")
            if len(remaining_videos) > 1:
                return self.process_next_video(remaining_videos[1:], output_folder, total_videos)
            else:
                return None, total_videos

def parse_args():
    parser = argparse.ArgumentParser(description='Generate Pose Annotations for all videos in a folder')
    parser.add_argument('input_folder', type=str, help='folder containing source videos')
    parser.add_argument('output_folder', type=str, help='output folder for pickle files')
    parser.add_argument('--device', type=str, default='cuda:0', help='device to use for inference')
    parser.add_argument('--skip-postproc', action='store_true', help='skip post-processing step')
    return parser.parse_args()

if __name__ == '__main__':
    args = parse_args()
    extractor = NTUPoseExtractor(device=args.device, skip_postproc=args.skip_postproc)
    extractor.process_video_folder(args.input_folder, args.output_folder)