import numpy as np
import cv2
from shapely.geometry import Polygon, box
import logging

class analysis_pose():
    def __init__(self):
        self.test = 0
        self.logger = logging.getLogger("module_logger")
        self.logger.setLevel(logging.INFO)
    def process_model(self,batch_data,model,model_config):
        images_batch = batch_data['images']
        camera_info_batch = batch_data['camera_info']
        results = model(images_batch, conf=model_config.get("conf"), classes=model_config.get("label_conf"), verbose=False)
        # self.time_logger.info(f"Mask: {results[0]}")
        detection_flags = []
        for i, detections in enumerate(results):       
            image = images_batch[i]     
            camera_id, camera_info = camera_info_batch[i]
            annotated_image, detection_flag = analysis_pose.annotate_image(self, image, detections, camera_info)
            # self.time_logger.info(f"results: {results}")
            results[i].orig_img = annotated_image
            detection_flags.append(detection_flag)
        return results,detection_flags
     
    def annotate_image(self, image, detections, camera_info):
        """

        """
        detection_flag = False
        # detected_labels = []
        mask = camera_info.get("config").get("mask")
        camera_id = camera_info.get("id")
        annotated_image = detections.plot()
        keypoints = detections.keypoints
        self.logger.info(f"keypoints: {keypoints}")
        # Return results
        return annotated_image, detection_flag
    
    def text_label(self, annotated_image, box, label="", color=(128, 128, 128), txt_color=(255, 255, 255), margin=5):

        x1, y1, x2, y2 = map(int, box)
        # Calculate the center of the bounding box
        x_center, y_center = int((box[0] + box[2]) / 2), int((box[1] + box[3]) / 2)
        # Get the size of the text
        text_size = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 2 - 0.1, 2)[0]
        # Calculate the top-left corner of the text (to center it)
        text_x = x_center - text_size[0] // 2
        text_y = y_center + text_size[1] // 2
        # Calculate the coordinates of the background rectangle
        rect_x1 = text_x - margin
        rect_y1 = text_y - text_size[1] - margin
        rect_x2 = text_x + text_size[0] + margin
        rect_y2 = text_y + margin
        # Draw the background rectangle
        cv2.rectangle(annotated_image, (rect_x1, rect_y1), (rect_x2, rect_y2), color, -1)
        # Draw the text on top of the rectangle
        cv2.putText(
            annotated_image,
            label,
            (text_x, text_y),
            cv2.FONT_HERSHEY_SIMPLEX,
            2 - 0.1,
            txt_color,
            2,
            lineType=cv2.LINE_AA,        
        )       
        cv2.rectangle(annotated_image, (x1, y1), (x2, y2), color, 4)

    def box_label(self, annotated_image, box, label="", color=(128, 128, 128), txt_color=(255, 255, 255)):

        p1, p2 = (int(box[0]), int(box[1])), (int(box[2]), int(box[3]))
        cv2.rectangle(annotated_image, p1, p2, color, thickness=2, lineType=cv2.LINE_AA)
        if label:
            w, h = cv2.getTextSize(label, cv2.FONT_HERSHEY_SIMPLEX, 1.8, 3)[0]  # text width, height
            h += 3  # add pixels to pad text
            outside = p1[1] >= h  # label fits outside box
            if p1[0] >annotated_image.shape[1] - w:  # shape is (h, w), check if label extend beyond right side of image
                p1 = annotated_image.shape[1] - w, p1[1]
            p2 = p1[0] + w, p1[1] - h if outside else p1[1] + h
            cv2.rectangle(annotated_image, p1, p2, color, -1, cv2.LINE_AA)  # filled
            cv2.putText(
                annotated_image,
                label,
                (p1[0], p1[1] - 2 if outside else p1[1] + h - 1),
                cv2.FONT_HERSHEY_SIMPLEX,
                1.8,
                txt_color,
                thickness=3,
                lineType=cv2.LINE_AA,
            )
            

        """
        Plot keypoints on the image.

        Args:
            kpts (torch.Tensor): Keypoints, shape [17, 3] (x, y, confidence).
            shape (tuple, optional): Image shape (h, w). Defaults to (640, 640).
            radius (int, optional): Keypoint radius. Defaults to 5.
            kpt_line (bool, optional): Draw lines between keypoints. Defaults to True.
            conf_thres (float, optional): Confidence threshold. Defaults to 0.25.
            kpt_color (tuple, optional): Keypoint color (B, G, R). Defaults to None.

        Note:
            - `kpt_line=True` currently only supports human pose plotting.
            - Modifies self.im in-place.
            - If self.pil is True, converts image to numpy array and back to PIL.
        """
        colors = Colors()  # create instance for 'from utils.plots import colors'
        nkpt, ndim = kpts.shape
        is_pose = nkpt == 17 and ndim in {2, 3}
        kpt_line &= is_pose  # `kpt_line=True` for now only supports human pose plotting
        for i, k in enumerate(kpts):
            color_k = kpt_color or (self.kpt_color[i].tolist() if is_pose else colors(i))
            x_coord, y_coord = k[0], k[1]
            if x_coord % shape[1] != 0 and y_coord % shape[0] != 0:
                if len(k) == 3:
                    conf = k[2]
                    if conf < conf_thres:
                        continue
                cv2.circle(self.im, (int(x_coord), int(y_coord)), radius, color_k, -1, lineType=cv2.LINE_AA)

        if kpt_line:
            ndim = kpts.shape[-1]
            for i, sk in enumerate(self.skeleton):
                pos1 = (int(kpts[(sk[0] - 1), 0]), int(kpts[(sk[0] - 1), 1]))
                pos2 = (int(kpts[(sk[1] - 1), 0]), int(kpts[(sk[1] - 1), 1]))
                if ndim == 3:
                    conf1 = kpts[(sk[0] - 1), 2]
                    conf2 = kpts[(sk[1] - 1), 2]
                    if conf1 < conf_thres or conf2 < conf_thres:
                        continue
                if pos1[0] % shape[1] == 0 or pos1[1] % shape[0] == 0 or pos1[0] < 0 or pos1[1] < 0:
                    continue
                if pos2[0] % shape[1] == 0 or pos2[1] % shape[0] == 0 or pos2[0] < 0 or pos2[1] < 0:
                    continue
                cv2.line(
                    annotated_image,
                    pos1,
                    pos2,
                    kpt_color or self.limb_color[i].tolist(),
                    thickness=int(np.ceil(self.lw / 2)),
                    lineType=cv2.LINE_AA,
                )
        if self.pil:
            # Convert im back to PIL and update draw
            self.fromarray(self.im)