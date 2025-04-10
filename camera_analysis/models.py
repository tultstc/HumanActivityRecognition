import logging
import cv2
import numpy as np
import torch
from mmengine import Config
from mmengine.structures import InstanceData
from mmaction.structures import ActionDataSample

try:
    from mmdet.apis import inference_detector, init_detector
except (ImportError, ModuleNotFoundError):
    raise ImportError('Failed to import `inference_detector` and `init_detector` form `mmdet.apis`')

logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

class DefaultVisualizer:
    def __init__(
            self,
            max_labels_per_bbox=5,
            plate='03045e-023e8a-0077b6-0096c7-00b4d8-48cae4',
            text_fontface=cv2.FONT_HERSHEY_DUPLEX,
            text_fontscale=0.5,
            text_fontcolor=(255, 255, 255),
            text_thickness=1,
            text_linetype=1):
        self.max_labels_per_bbox = max_labels_per_bbox
        self.text_fontface = text_fontface
        self.text_fontscale = text_fontscale
        self.text_fontcolor = text_fontcolor
        self.text_thickness = text_thickness
        self.text_linetype = text_linetype

        def hex2color(h):
            return (int(h[:2], 16), int(h[2:4], 16), int(h[4:], 16))

        plate = plate.split('-')
        self.plate = [hex2color(h) for h in plate]

    def draw_one_image(self, frame, bboxes, preds):
        for bbox, pred in zip(bboxes, preds):
            # draw bbox
            box = bbox.astype(np.int64)
            st, ed = tuple(box[:2]), tuple(box[2:])
            cv2.rectangle(frame, st, ed, (0, 0, 255), 2)

            # draw texts
            for k, (label, score) in enumerate(pred):
                if k >= self.max_labels_per_bbox:
                    break
                text = f'{label}: {score:.4f}'
                location = (0 + st[0], 18 + k * 18 + st[1])
                textsize = cv2.getTextSize(text, self.text_fontface,
                                         self.text_fontscale,
                                         self.text_thickness)[0]
                textwidth = textsize[0]
                diag0 = (location[0] + textwidth, location[1] - 14)
                diag1 = (location[0], location[1] + 2)
                cv2.rectangle(frame, diag0, diag1, self.plate[k + 1], -1)
                cv2.putText(frame, text, location, self.text_fontface,
                          self.text_fontscale, self.text_fontcolor,
                          self.text_thickness, self.text_linetype)

        return frame

class MmdetHumanDetector:
    def __init__(self, config, ckpt, device, score_thr, person_classid=0):
        self.device = torch.device(device)
        self.model = init_detector(config, ckpt, device=device)
        self.person_classid = person_classid
        self.score_thr = score_thr

    def _do_detect(self, image):
        det_data_sample = inference_detector(self.model, image)
        pred_instance = det_data_sample.pred_instances.cpu().numpy()
        valid_idx = np.logical_and(pred_instance.labels == self.person_classid,
                                 pred_instance.scores > self.score_thr)
        bboxes = pred_instance.bboxes[valid_idx]
        return bboxes

    def predict(self, frames):
        keyframe = frames[len(frames) // 2]
        bboxes = self._do_detect(keyframe)
        if isinstance(bboxes, np.ndarray):
            bboxes = torch.from_numpy(bboxes).to(self.device)
        elif isinstance(bboxes, torch.Tensor) and bboxes.device != self.device:
            bboxes = bboxes.to(self.device)
        return bboxes

class StdetPredictor:
    def __init__(self, config, checkpoint, device, score_thr, label_map_path):
        self.score_thr = score_thr
        config.model.backbone.pretrained = None
        model = init_detector(config, checkpoint, device=device)
        self.model = model
        self.device = device

        with open(label_map_path) as f:
            lines = f.readlines()
        lines = [x.strip().split(': ') for x in lines]
        self.label_map = {int(x[0]): x[1] for x in lines}
        try:
            if config['data']['train']['custom_classes'] is not None:
                self.label_map = {
                    id + 1: self.label_map[cls]
                    for id, cls in enumerate(config['data']['train']['custom_classes'])
                }
        except KeyError:
            pass

    def predict(self, frames, bboxes, frames_inds, img_shape):
        if len(bboxes) == 0:
            return []

        input_array = np.stack([frames[idx] for idx in frames_inds]).transpose((3, 0, 1, 2))[np.newaxis]
        input_tensor = torch.from_numpy(input_array).to(self.device)
        datasample = ActionDataSample()
        datasample.proposals = InstanceData(bboxes=bboxes)
        datasample.set_metainfo(dict(img_shape=img_shape))

        with torch.no_grad():
            result = self.model(inputs=input_tensor, data_samples=[datasample], mode='predict')
            
        scores = result[0].pred_instances.scores
        preds = []
        for _ in range(bboxes.shape[0]):
            preds.append([])
        for class_id in range(scores.shape[1]):
            if class_id not in self.label_map:
                continue
            for bbox_id in range(bboxes.shape[0]):
                if scores[bbox_id][class_id] > self.score_thr:
                    preds[bbox_id].append((
                        self.label_map[class_id],
                        scores[bbox_id][class_id].item()
                    ))
        return preds

def init_models():
    # Default configs
    device = "cuda:0"
    det_config = 'demo/demo_configs/faster-rcnn_r50_fpn_2x_coco_infer.py'
    det_checkpoint = ('http://download.openmmlab.com/mmdetection/v2.0/faster_rcnn/faster_rcnn_r50_fpn_2x_coco/faster_rcnn_r50_fpn_2x_coco_bbox_mAP-0.384_20200504_210434-a5d8aa15.pth')
    det_score_thr = 0.9
    
    config_file = ('configs/skeleton/posec3d/slowonly_r50_8xb16-u48-240e_ntu60-xsub-keypoint.py')
    checkpoint = ('slowonly_r50_8xb16-u48-240e_ntu60-xsub-keypoint/best_acc_top1_epoch_1.pth')
    action_score_thr = 0.5
    label_map_path = 'tools/data/kinetics/label_map_k400.txt'

    config = Config.fromfile(config_file)
    config['model']['test_cfg']['rcnn'] = dict(action_thr=0)

    human_detector = MmdetHumanDetector(
        det_config, det_checkpoint, device, det_score_thr)
    
    stdet_predictor = StdetPredictor(
        config=config,
        checkpoint=checkpoint,
        device=device,
        score_thr=action_score_thr,
        label_map_path=label_map_path)

    visualizer = DefaultVisualizer()

    return human_detector, stdet_predictor, visualizer