import numpy as np
import cv2
from shapely.geometry import Polygon, box
import logging

class analysis_zone():
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
            annotated_image, detection_flag = analysis_zone.annotate_image(self, image, detections, camera_info)
            # self.time_logger.info(f"results: {results}")
            results[i].orig_img = annotated_image
            detection_flags.append(detection_flag)
        return results,detection_flags
     
    def annotate_image(self, image, detections, camera_info):
        """
        Mark the image and filter the matching areas based on the mask.
        :param image: original image (np.ndarray)
        :param detections: YOLO model detection results
        :param camera_info: additional information about the camera
        :param model_name: model name
        :return: the annotated image (np.ndarray), whether the target is detected (bool), the detected tag name (str)
        """
        detection_flag = False
        # detected_labels = []
        mask = camera_info.get("config").get("mask")
        camera_id = camera_info.get("id")
        annotated_image = image.copy()
        # self.time_logger.info(f"mask: {mask['polygon1']}")
        # If a mask is provided, checks if the target is within the mask range
        if mask:
            # Draw each polygon on the image
            for key, polygon in mask.items():
                # Convert points to a numpy array and reshape for OpenCV
                pts = np.array(polygon, np.int32).reshape((-1, 1, 2))
                # Draw the polygon (image, points, isClosed, color, thickness)
                cv2.polylines(annotated_image, [pts], isClosed=True, color=(0, 255, 0), thickness=2)

            # Make sure the test results are valid
            if detections.boxes is None or len(detections.boxes) == 0:
                self.logger.debug(f"[{camera_id}] No detections found to annotate.")
                return annotated_image, False
            
            # Extract test results
            boxes = detections.boxes.xyxy.cpu().numpy()
            confidences = detections.boxes.conf.cpu().numpy()
            class_ids = detections.boxes.cls.cpu().numpy().astype(int)

            for i, (bbox, conf, cls_id) in enumerate(zip(boxes, confidences, class_ids)):
                label = detections.names[int(cls_id)]                
                # Create a Shapely box object for the bounding box
                bbox_polygon = box(*bbox)
                for key, polygon_vertices  in mask.items():
                    polygon = Polygon(polygon_vertices)
                    if polygon.contains(bbox_polygon):
                        color = (0, 0, 255)  # Red
                        detection_flag = True   
                    elif polygon.intersects(bbox_polygon):
                        color = (0, 210, 255)  # Orange
                        detection_flag = True  
                    else:
                        color = (255, 0, 0)  # Default green
                    label_text = f"{label} {conf:.2f}"    
                    analysis_zone.box_label(self, annotated_image, bbox, label_text, color)
        else:
            self.logger.debug(f"[{camera_id}] Mask is None. Processing the entire image.")
            annotated_image = detections.plot()
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
            
