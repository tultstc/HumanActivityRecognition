import pickle
import numpy as np

with open("posec3d_keypoints/train/S001C001P001R001A001.pkl", "rb") as f:
    keypoint_data = pickle.load(f)

print("Các keys trong file:", keypoint_data.keys())

if isinstance(keypoint_data, dict):
    for key, value in keypoint_data.items():
        print(f"{key}: {value}")
