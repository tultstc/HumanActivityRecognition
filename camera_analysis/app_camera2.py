import logging
import asyncio
import time
import os

import cv2
import redis
import json
import mmengine
import numpy as np
import torch
import torch.nn as nn
import os.path as osp

from ultralytics import YOLO
from ApiService import ApiService
from image_storage import ImageStorage
from logging_config import *
from mmaction.apis import init_recognizer
from mmengine.utils import track_iter_progress
from mmengine.structures import InstanceData
from mmdet.apis import init_detector
from mmpose.apis import init_model
from mmpose.structures import PoseDataSample
from mmpose.visualization import PoseLocalVisualizer
from mmpose.apis import MMPoseInferencer
from typing import List, Optional, Tuple, Union
from mmengine.dataset import Compose, pseudo_collate
from mmaction.structures import ActionDataSample
from mmengine.registry import init_default_scope

class MainApp:
    def __init__(self):
        configure_logging()
        self.logger = logging.getLogger("main_logger")
        self.file_logger = logging.getLogger("app_logger")
        self.logger.setLevel(logging.INFO)
        self.lic_check = True
        self.camera = os.getenv('CAMERA')
        self.api_service = ApiService(base_url=os.getenv("WEB_SERVICE_URL"))
        self.last_sent_timestamps = {}
        self.SLEEP_INTERVAL = 0.1
        self.camera_frame_count = {}
        self.last_label = None
        self.pose_buffer = {}
        self.window_size = 10
        self.pose_config = 'demo/demo_configs/td-hm_hrnet-w32_8xb64-210e_coco-256x192_infer.py'
        self.pose_checkpoint = 'demo/hrnet_w32_coco_256x192-c78dce93_20200708.pth'
        self.det_config = 'demo/demo_configs/faster-rcnn_r50_fpn_2x_coco_infer.py'
        self.det_checkpoint = 'demo/faster_rcnn_r50_fpn_2x_coco_bbox_mAP-0.384_20200504_210434-a5d8aa15.pth'
        self.config = mmengine.Config.fromfile('configs/skeleton/posec3d/slowonly_r50_8xb16-u48-240e_ntu60-xsub-keypoint.py')
        self.action_model = init_recognizer(self.config, 'slowonly_r50_8xb16-u48-240e_ntu60-xsub-keypoint/epoch_24.pth')
        self.label_map = 'tools/data/kinetics/label_map_k400.txt'
        self.pose_estimator = init_model(self.pose_config, self.pose_checkpoint, 'cuda:0')
        self.detector = init_detector(config=self.det_config, checkpoint=self.det_checkpoint, device='cuda:0')
        self.inferencer = MMPoseInferencer(pose2d='human', det_weights="demo/rtmdet_m_8xb32-100e_coco-obj365-person-235e8209.pth", pose2d_weights="demo/rtmpose-m_simcc-body7_pt-body7_420e-256x192-e48f03d0_20230504.pth", device='cuda:0')
        self.last_actions = {}

        self.yolo_model = YOLO("yolo11n.pt")
        
        self.redis_host = "redis"
        self.redis_port = 6379
        self.r = redis.Redis(host=self.redis_host, port=self.redis_port, db=0)
        self.image_storage = ImageStorage(self.r)

        self.init_dirs()

    def init_dirs(self):
        start_time = time.monotonic()
        self.BASE_SAVE_DIR = "logs"
        self.ALARM_SAVE_DIR = os.path.join(self.BASE_SAVE_DIR, "alarm_images")

        for dir_path in [
            self.ALARM_SAVE_DIR,
        ]:
            if not os.path.exists(dir_path):
                os.makedirs(dir_path)

        self.logger.debug(f"Directories initialized in {time.monotonic() - start_time:.2f} seconds")


    async def fetch_camera_status(self):
        loop = asyncio.get_event_loop()
        status = await loop.run_in_executor(None, self.image_storage.fetch_camera_status)
        return status

    async def fetch_snapshot(self, camera_id):       
        """Get the latest image of the specified camera from Redis"""
        redis_key = f"camera_{camera_id}_latest_frame"
        loop = asyncio.get_event_loop()
        image = await loop.run_in_executor(None, self.image_storage.fetch_image, redis_key)
        return image

    async def process_camera(self, camera_id, camera_info, images_batches):
        img = await self.fetch_snapshot(camera_id)
        if img is not None:
            model_id = camera_info.get("model_id")
            if model_id not in images_batches: 
                images_batches[model_id] = {
                    'images': [],
                    'camera_info': []
                }
            images_batches[model_id]['images'].append(img)
            images_batches[model_id]['camera_info'].append((camera_id, camera_info))
            self.camera_frame_count[camera_id]['count'] += 1
            current_time = time.monotonic()
            elapsed = current_time - self.camera_frame_count[
                camera_id]['last_time']
            if elapsed >= 10.0:
                fps = self.camera_frame_count[camera_id]['count'] / elapsed
                self.camera_frame_count[camera_id]['count'] = 0
                self.camera_frame_count[camera_id]['last_time'] = current_time
                self.logger.debug(f"Camera {camera_id} FPS: {fps}")
        else:
            self.logger.warning(f"No image fetched for camera {camera_id}")

    def convert_inferencer_output_batch(self, result_dict, image_shape):
        pose_data_sample = PoseDataSample()
        
        predictions = result_dict['predictions'][0] if result_dict['predictions'] else []
        visualization = result_dict['visualization'][0] if result_dict['visualization'] else None
        
        if predictions:
            keypoints = np.array([pred['keypoints'] for pred in predictions])
            keypoint_scores = np.array([pred['keypoint_scores'] for pred in predictions])
            
            if 'bboxes' in predictions[0]:
                bboxes = np.array([pred['bboxes'] for pred in predictions])
            else:
                bboxes = np.zeros((len(predictions), 4), dtype=np.float32)
                for i, kpts in enumerate(keypoints):
                    valid_kpts = kpts[keypoint_scores[i] > 0.3]
                    if len(valid_kpts) > 0:
                        x_min, y_min = np.min(valid_kpts, axis=0)
                        x_max, y_max = np.max(valid_kpts, axis=0)
                        
                        width, height = x_max - x_min, y_max - y_min
                        x_min = max(0, x_min - width * 0.1)
                        y_min = max(0, y_min - height * 0.1)
                        x_max = min(image_shape[1], x_max + width * 0.1)
                        y_max = min(image_shape[0], y_max + height * 0.1)
                        bboxes[i] = [x_min, y_min, x_max, y_max]
            
            if len(predictions) > 0 and 'bbox_scores' in predictions[0]:
                bbox_scores = np.array([pred['bbox_scores'] for pred in predictions])
            else:
                bbox_scores = np.mean(keypoint_scores, axis=1)
            
            pred_instances_data = dict(
                keypoints=keypoints,
                keypoint_scores=keypoint_scores,
                bboxes=bboxes,
                bbox_scores=bbox_scores
            )
            
            pose_data_sample.pred_instances = InstanceData(**pred_instances_data)
            
            if hasattr(self.pose_estimator, 'dataset_meta'):
                pose_data_sample.dataset_meta = self.pose_estimator.dataset_meta
            
            pose_result = {
                'keypoints': keypoints,
                'keypoint_scores': keypoint_scores,
                'bboxes': bboxes,
                'bbox_scores': bbox_scores
            }
        else:
            num_keypoints = self.pose_estimator.dataset_meta['num_keypoints']
            pred_instances_data = dict(
                keypoints=np.empty(shape=(0, num_keypoints, 2)),
                keypoint_scores=np.empty(shape=(0, num_keypoints), dtype=np.float32),
                bboxes=np.empty(shape=(0, 4), dtype=np.float32),
                bbox_scores=np.empty(shape=(0), dtype=np.float32)
            )
            pose_data_sample.pred_instances = InstanceData(**pred_instances_data)
            
            if hasattr(self.pose_estimator, 'dataset_meta'):
                pose_data_sample.dataset_meta = self.pose_estimator.dataset_meta
            
            pose_result = pred_instances_data
        
        return pose_data_sample, pose_result, visualization
    
    def inference_recognizer(self, model: nn.Module,
                         video: Union[str, dict],
                         test_pipeline: Optional[Compose] = None
                         ) -> ActionDataSample:
        if test_pipeline is None:
            cfg = model.cfg
            init_default_scope(cfg.get('default_scope', 'mmaction'))
            test_pipeline_cfg = cfg.test_pipeline
            test_pipeline = Compose(test_pipeline_cfg)

        input_flag = None
        if isinstance(video, dict):
            input_flag = 'dict'
        elif isinstance(video, str) and osp.exists(video):
            if video.endswith('.npy'):
                input_flag = 'audio'
            else:
                input_flag = 'video'
        else:
            raise RuntimeError(f'The type of argument `video` is not supported: '
                            f'{type(video)}')

        if input_flag == 'dict':
            data = video
        if input_flag == 'video':
            data = dict(filename=video, label=-1, start_index=0, modality='RGB')
        if input_flag == 'audio':
            data = dict(
                audio_path=video,
                total_frames=len(np.load(video)),
                start_index=0,
                label=-1)

        data = test_pipeline(data)
        data = pseudo_collate([data])

        # Forward the model
        with torch.no_grad():
            result = model.test_step(data)[0]

        return result
    
    def inference_skeleton(self, model: nn.Module,
                       pose_results: List[dict],
                       img_shape: Tuple[int],
                       test_pipeline: Optional[Compose] = None
                       ) -> ActionDataSample:
        if test_pipeline is None:
            cfg = model.cfg
            init_default_scope(cfg.get('default_scope', 'mmaction'))
            test_pipeline_cfg = cfg.test_pipeline
            test_pipeline = Compose(test_pipeline_cfg)

        h, w = img_shape
        num_keypoint = pose_results[0]['keypoints'].shape[1]
        num_frame = len(pose_results)
        num_person = max([len(x['keypoints']) for x in pose_results])
        fake_anno = dict(
            frame_dict='',
            label=-1,
            img_shape=(h, w),
            origin_shape=(h, w),
            start_index=0,
            modality='Pose',
            total_frames=num_frame)

        keypoint = np.zeros((num_frame, num_person, num_keypoint, 2),
                            dtype=np.float16)
        keypoint_score = np.zeros((num_frame, num_person, num_keypoint),
                                dtype=np.float16)

        for f_idx, frm_pose in enumerate(pose_results):
            frm_num_persons = frm_pose['keypoints'].shape[0]
            for p_idx in range(frm_num_persons):
                keypoint[f_idx, p_idx] = frm_pose['keypoints'][p_idx]
                keypoint_score[f_idx, p_idx] = frm_pose['keypoint_scores'][p_idx]

        fake_anno['keypoint'] = keypoint.transpose((1, 0, 2, 3))
        fake_anno['keypoint_score'] = keypoint_score.transpose((1, 0, 2))
        return self.inference_recognizer(model, fake_anno, test_pipeline)
    
    def draw_action_label(self, image, action_name, confidence, position=(30, 30)):
        labeled_image = image.copy()
        font = cv2.FONT_HERSHEY_SIMPLEX
        text = f"{action_name} ({confidence:.2f})"
        
        text_size = cv2.getTextSize(text, font, 0.7, 2)[0]
        cv2.rectangle(
            labeled_image, 
            (position[0] - 5, position[1] - text_size[1] - 5),
            (position[0] + text_size[0] + 5, position[1] + 5),
            (0, 0, 0), 
            -1
        )
        
        cv2.putText(
            labeled_image, 
            text, 
            position, 
            font, 
            0.7, 
            (255, 255, 255), 
            2
        )
        
        return labeled_image
    
    def detect_phone(self, image):
        results = self.yolo_model(image, classes=67)
        phone_detections = []
        for result in results:
            boxes = result.boxes
            for box in boxes:
                x1, y1, x2, y2 = box.xyxy[0].cpu().numpy()
                confidence = box.conf[0].cpu().numpy()
                center_x = int((x1 + x2) / 2)
                center_y = int((y1 + y2) / 2)
                
                phone_detections.append({
                    'bbox': [x1, y1, x2, y2],
                    'confidence': confidence,
                    'center': (center_x, center_y)
                })
                
        return phone_detections
    
    def draw_phone_detections(self, image, phone_detections):
        annotated_image = image.copy()
        for detection in phone_detections:
            x1, y1, x2, y2 = [int(coord) for coord in detection['bbox']]
            cv2.rectangle(annotated_image, (x1, y1), (x2, y2), (0, 255, 0), 2)
            center_x, center_y = detection['center']
            cv2.circle(annotated_image, (center_x, center_y), 5, (0, 0, 255), -1)
            text = f"Phone: ({center_x}, {center_y}) {detection['confidence']:.2f}"
            cv2.putText(
                annotated_image,
                text,
                (x1, y1 - 10),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.5,
                (0, 255, 0),
                2
            )
        return annotated_image

    async def process_single_camera(self, camera_id, camera_info):
        """Process a single camera with the specified ID"""
        img = await self.fetch_snapshot(camera_id)
        if img is None:
            self.logger.warning(f"No image fetched for camera {camera_id}")
            return
        
        self.camera_frame_count[1]['count'] += 1
        current_time = time.monotonic()
        elapsed = current_time - self.camera_frame_count[1]['last_time']
        if elapsed >= 10.0:
            fps = self.camera_frame_count[1]['count'] / elapsed
            self.camera_frame_count[1]['count'] = 0
            self.camera_frame_count[1]['last_time'] = current_time
            self.logger.debug(f"Camera {self.camera} FPS: {fps}")
        
        model_id = camera_info.get("model_id")
        if model_id == 3:
            start_time = time.monotonic()
            
            infer_results = list(self.inferencer([img]))
            
            if infer_results and len(infer_results) > 0:
                result_dict = infer_results[0]
                
                _, pose_result, annotated_image = self.convert_inferencer_output_batch(
                    result_dict, img.shape[:2]
                )
                
                check_valid_person_detected = len(pose_result["bbox_scores"]) > 0 and pose_result["bbox_scores"][0] > 0.4
                
                # Valid Person
                if check_valid_person_detected:
                    phone_detections = self.detect_phone(img)
                    
                    last_action_label = None
                    last_action_confidence = 0.0
                    
                    if hasattr(self, 'last_actions') and camera_id in self.last_actions:
                        last_action_label = self.last_actions[camera_id]['label']
                        last_action_confidence = self.last_actions[camera_id]['confidence']
                    
                    if annotated_image is not None:
                        if last_action_label:
                            annotated_image = self.draw_action_label(
                                annotated_image, 
                                last_action_label, 
                                last_action_confidence
                            )
                        
                        if phone_detections:
                            annotated_image = self.draw_phone_detections(annotated_image, phone_detections)
                            self.logger.info(f"Camera {camera_id}: Detected {len(phone_detections)} phones")
                        
                    else:
                        annotated_image = img.copy()
                    
                    if camera_id not in self.pose_buffer:
                        self.pose_buffer[camera_id] = []
                    
                    self.pose_buffer[camera_id].append(pose_result)
                    
                    if len(self.pose_buffer[camera_id]) >= self.window_size:
                        action_result = self.inference_skeleton(
                            self.action_model, 
                            self.pose_buffer[camera_id], 
                            img.shape[:2]
                        )
                        action_label = action_result.pred_label.item()
                        action_confidence = action_result.pred_score.max().item()
                        
                        try:
                            with open(self.label_map, 'r') as f:
                                labels = [line.strip() for line in f]
                            action_name = labels[action_label] if action_label < len(labels) else f"Unknown-{action_label}"
                        except:
                            action_name = f"Action-{action_label}"
                        
                        if not hasattr(self, 'last_actions'):
                            self.last_actions = {}
                        
                        self.last_actions[camera_id] = {
                            'label': action_name,
                            'confidence': action_confidence
                        }
                        
                        annotated_image = self.draw_action_label(
                            annotated_image, 
                            action_name, 
                            action_confidence
                        )
                        
                        if phone_detections:
                            annotated_image = self.draw_phone_detections(annotated_image, phone_detections)
                        
                        self.logger.info(f"Camera {camera_id}: {action_name} (confidence: {action_confidence:.2f})")
                        self.pose_buffer[camera_id] = self.pose_buffer[camera_id][7:]
                        
                    else:
                        action_name = self.last_actions.get(camera_id, {}).get('label', "No Action")
                        action_confidence = self.last_actions.get(camera_id, {}).get('confidence', 0.0)
                        
                else:
                    redis_key_boxed = f"camera_{camera_id}_boxed_image"
                    annotated_image = img.copy()
                    self.image_storage.save_image(redis_key_boxed, annotated_image)
                    return
                
                annotated_image = self.draw_action_label(annotated_image, action_name, action_confidence)
                redis_key_boxed = f"camera_{camera_id}_boxed_image"
                self.image_storage.save_image(redis_key_boxed, annotated_image)
                
            else:
                self.logger.warning(f"No inference results for camera {camera_id}")
            
            self.logger.debug(f"Time to process camera {camera_id}: {time.monotonic() - start_time:.5f} seconds")
    
    async def main_loop(self):
        check_time = time.monotonic()       
        camera_status = await self.fetch_camera_status()
        if not camera_status:
            self.logger.warning("No camera status received")

        if f"{self.camera}" in camera_status:
            self.camera_frame_count[1] = {
                'count': 0,
                'last_time': check_time,
            }
        else:
            self.logger.warning(f"Camera ID {self.camera} not found in camera status")
                
        while True:
            start_time = time.monotonic()
            
            if f"{self.camera}" in camera_status and camera_status[f"{self.camera}"]["alive"] == "True" and camera_status[f"{self.camera}"]["last_image_timestamp"] != 'unknown' and camera_status[f"{self.camera}"]["camera_info"]:
                camera_info = json.loads(camera_status[f"{self.camera}"]["camera_info"])
                if camera_info:
                    await self.process_single_camera(self.camera, camera_info)
            else:
                self.logger.warning(f"Camera ID {self.camera} is not available or not ready for processing")

            elapsed = start_time - check_time
            if elapsed >= 5.0:
                self.logger.info(f"Processing Analysis is about {elapsed:.2f} seconds")
                check_time = start_time
                camera_status = await self.fetch_camera_status()
                if not camera_status:
                    self.logger.warning("No camera status received")
                    await asyncio.sleep(self.SLEEP_INTERVAL)
                    continue      
            elapsed_time = time.monotonic() - start_time
            adjusted_sleep = max(0.001, self.SLEEP_INTERVAL - elapsed_time)
            await asyncio.sleep(adjusted_sleep)

if __name__ == "__main__":
    app = MainApp()
    asyncio.run(app.main_loop())