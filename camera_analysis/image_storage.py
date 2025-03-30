import cv2
import numpy as np
import logging
import time
import pickle

class ImageStorage:
    def __init__(self, redis_instance):
        self.r = redis_instance

    def save_image(self, key, image):
        """Save image to Redis。"""
        is_success, buffer = cv2.imencode(".jpg", image, [cv2.IMWRITE_JPEG_QUALITY, 70])
        if is_success:
            try:
                self.r.set(key, buffer.tobytes())
                # logging.debug(f"Image saved to Redis under key {key}.")
            except Exception as e:
                logging.error(f"Failed to save image to Redis: {str(e)}")
        else:
            logging.error("Failed to encode image to buffer.")

    def save_image_raw(self, key, image):
        """Save image to Redis。"""
        try:
            self.r.set(key, pickle.dumps(image))
            # logging.debug(f"Image saved to Redis under key {key}.")
        except Exception as e:
            logging.error(f"Failed to save image to Redis: {str(e)}")       

    def fetch_image(self, key):
        """Get images from Redis。"""
        try:
            # start_time = time.monotonic()
            image_data = self.r.get(key)
            if image_data:
                np_arr = np.frombuffer(image_data, np.uint8)
                img = cv2.imdecode(np_arr, cv2.IMREAD_COLOR)
                # logging.debug(f"Fetched image data from Redis for key {key} in {time.monotonic() - start_time:.2f} seconds")
                return img
            else:
                logging.error(f"No image data found in Redis for key {key}")
                return None
        except Exception as e:
            logging.error(f"Error fetching image from Redis for key {key}: {str(e)}")
            return None
    def fetch_camera_status(self):
        status = {}
        for key in self.r.keys("camera_*_status"):
            camera_id = key.decode().split('_')[1]
            if self.r.exists(f'camera_{camera_id}_status'):
                camera_status = self.r.get(key)
                last_timestamp = self.r.get(f'camera_{camera_id}_last_timestamp')
                #Config TuanDA
                camera_info = self.r.get(f'camera_{camera_id}_info')
                if  camera_info is not None:
                    camera_info = camera_info.decode("utf-8")
                else:
                    camera_info = {}   
                # print(f"Check:{camera_info}")
                if camera_status is not None and last_timestamp is not None:
                    camera_status = camera_status.decode()
                    last_timestamp = last_timestamp.decode()
                    status[camera_id] = {
                        "alive": camera_status,
                        "last_image_timestamp": last_timestamp,
                        "camera_info": camera_info
                    }
                else:
                    status[camera_id] = {
                        "alive": "unknown",
                        "last_image_timestamp": "unknown",
                        "camera_info": camera_info
                    }
        return status