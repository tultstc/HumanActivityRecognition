import os
import hashlib
from urllib import request
from pathlib import Path
from ultralytics import YOLO
import logging

class YOLOModel:
    def __init__(self, model_urls, default_conf=0.5, label_conf=None):
        self.logger = logging.getLogger('YOLOModel')
        self.models = self._download_and_load_models(model_urls)
        self.model_names = [model.names for model in self.models]
        self.default_conf = default_conf
        self.label_conf = label_conf if label_conf else {}

    def _download_and_load_models(self, model_urls):
        models = []
        for model_url in model_urls:
            model_path = self._download_model(model_url)
            model = self._load_model(model_path)
            models.append(model)
        return models

    def _download_model(self, model_url):
        # Create a temporary directory to store the model
        tmp_dir = Path("tmp")
        tmp_dir.mkdir(exist_ok=True)

        # Generate unique filename from URL
        filename = hashlib.md5(model_url.encode('utf-8')).hexdigest() + ".pt"
        file_path = tmp_dir / filename

        # If the file does not exist, download it
        if not file_path.exists():
            print("Download model...")
            request.urlretrieve(model_url, file_path)
        else:
            print("The model already exists, no need to re-download it.")

        return file_path

    def _load_model(self, model_path):
        # Load model
        print("Load model...")
        return YOLO(model_path)

    def predict(self, images):
        results = []
        for image in images:
            image_results = []
            for model in self.models:
                data = model(image)
                filtered_data = self._filter_predictions(data)
                image_results.append(filtered_data)
            results.append(image_results)
        return results

    def _filter_predictions(self, predictions):
        # Iterate over each prediction result, applying custom conf filtering
        filtered_predictions = []
        for pred in predictions:
            filtered_boxes = []
            for box in pred.boxes:
                class_id = int(box.cls[0])  # Assume that each box has only one class
                conf = box.conf[0]
                label = self.model_names[0][class_id]  # Assume all models share the same class name

                # Check if there is a custom conf for this label
                label_specific_conf = self.label_conf.get(label, self.default_conf)
                
                # If the confidence value of the predicted box is greater than or equal to the specified threshold, the box is retained
                if conf >= label_specific_conf:
                    filtered_boxes.append(box)
            
            # If there is a box that meets the conditions, add it to the filtered results
            if filtered_boxes:
                pred.boxes = filtered_boxes
                filtered_predictions.append(pred)

        return filtered_predictions
