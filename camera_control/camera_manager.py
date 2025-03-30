# camera_manager.py
import threading
import redis
import requests
import logging
import os
import time  # Add time module to use sleep
import json
from logging_config import *

class CameraManager:
    def __init__(self):
        # Default Settings 
        self.redis_client = redis.Redis(host="redis", port=6379, db=0)
        self.worker_id = 1
        self.worker_key = f"worker_{self.worker_id}_urls"
        self.WEB_SERVICE_URL = os.getenv('WEB_SERVICE_URL')
        self.num_workers = int(os.getenv("NUM_WORKERS", 1))  # Number of workers
        self.logger = logging.getLogger("main_logger")
        self.logger.setLevel(logging.INFO)
        self.lic_check = LicCheck()

    def clear_old_cameras(self, current_camera_ids):
        # Iterate over all camera-related keys, assuming camera-related keys start with 'camera_'
        for key in self.redis_client.scan_iter("camera_*"):
            # Extract the camera ID from the key name (assuming the key is of the form 'camera_{camera_id}_data')
            key_str = key.decode("utf-8")
            camera_id = key_str.split("_")[1]
            # self.logger.info(f'camera_id: {camera_id}')
            if int(camera_id) not in current_camera_ids:
                # If the camera ID is not in the current library, delete the key
                self.redis_client.delete(key)
                self.logger.info(f"Old camera data has been deleted from Queue, camera ID：{camera_id}")

        # Proceed to delete camera list keys for all workers
        # for worker_id in range(1, self.num_workers + 1):
        #     worker_key = f"worker_{worker_id}_urls"
        #     self.redis_client.delete(worker_key)
        #     self.logger.info(f"Old camera data for worker {worker_id} has been cleared.")
        #TuanDA
        cursor = 0  # Start from the beginning of the keyspace
        while True:
            # Use SCAN to find matching keys
            cursor, keys = self.redis_client.scan(cursor=cursor, match="*_urls", count=100)
            
            if keys:
                # Delete the matched keys in a batch
                self.redis_client.delete(*keys)
            
            # If the cursor is 0, we have iterated through all keys
            if cursor == 0:
                break
    def clear_all_cameras(self):
        # Iterate over all camera-related keys, assuming camera-related keys start with 'camera_'
        for key in self.redis_client.scan_iter("camera_*"):
            # Extract the camera ID from the key name (assuming the key is of the form 'camera_{camera_id}_data')
            self.redis_client.delete(key)               
        self.logger.warning(f"All camera data has been deleted from Queue")

    def clear_all_workers(self):
        # Iterate over all camera-related keys, assuming camera-related keys start with 'camera_'
        for key in self.redis_client.scan_iter("worker_*"):
            # Extract the camera ID from the key name (assuming the key is of the form 'camera_{camera_id}_data')
            self.redis_client.delete(key)               
        self.logger.warning(f"All workers data has been deleted from Queue")
        

    def fetch_and_update_cameras(self, previous_camera_ids):
        # Get the latest camera list from the server  
        url = f"{self.WEB_SERVICE_URL}/api/cameras"
        headers = {}
        response = requests.get(url, headers=headers)
        if response.status_code == 200:
            # try:
                # Assume that response.json() returns a list
                camera_data = response.json()  
                current_camera_ids = set(camera["id"] for camera in camera_data)
                updated = False  # Is the mark updated?

                if current_camera_ids != previous_camera_ids:
                    # If the camera list changes, update Redis
                    self.clear_old_cameras(current_camera_ids)
                    updated = True  # Marked with changes

                for camera in camera_data:
                    worker_id = camera["id"] % self.num_workers + 1
                    worker_key = f"worker_{worker_id}_urls"
                    redis_key = f"{camera['id']}|{camera['stream_url']}"
                    # self.logger.info(f"{camera['id']} Info:{camera}")           
                    self.redis_client.set(f"camera_{camera['id']}_info", json.dumps(camera))

                    # If the URL changes, delete the old key and add a new key
                    # Check if the camera is in Redis
                    existing_urls = self.redis_client.smembers(worker_key)
                    matching_key = next(
                        (
                            key
                            for key in existing_urls
                            if key.decode().startswith(f"{camera['id']}|")
                        ),
                        None,
                    )

                    if matching_key is None or matching_key.decode() != redis_key:
                        # Delete the old key, if it exists
                        if matching_key:
                            self.redis_client.srem(worker_key, matching_key)
                            self.logger.info(f"Old camera removed {camera['id']} URL。")

                        # Add new key
                        self.redis_client.sadd(worker_key, redis_key)
                        self.logger.info(f"Updated new URL for camera {camera['id']} to Queue, located in worker {worker_id}。")
                        updated = True  # Marked as updated

                if updated:
                    # Publish update events to all workers
                    for worker_id in range(1, self.num_workers + 1):
                        worker_key = f"worker_{worker_id}_urls"
                        self.redis_client.publish(f"{worker_key}_update", "updated")
                        self.logger.info(f"Updates for worker {worker_id} have been published.")

                    # Update previous camera ID list
                    previous_camera_ids.clear()
                    previous_camera_ids.update(current_camera_ids)

            # except Exception as e:
            #     self.logger.error(f"An error occurred while processing camera data: {e}")
        else:
            logging.error(f"Request failed, status code: {response.status_code}")

    def monitor_cameras(self):
        # Regularly check the camera list for changes
        previous_camera_ids = set()
        lic_time = 0
        lic_info = True
        self.redis_client.set(f"system_status", 1)
        while True:                     
            if(lic_time > 12 or lic_time == 0): 
                if lic_time != 0: 
                    lic_time = 0
                    lic_info = self.lic_check.check(False)['status']                    
                    if lic_info:
                        self.redis_client.set(f"system_status", 1)
                    else:
                        self.clear_all_workers()
                        self.clear_all_cameras()
                        self.redis_client.set(f"system_status", 0)   
                        self.logger.error(f"Check system status: {lic_info}")                     
            lic_time += 1
            if lic_info:        
                self.fetch_and_update_cameras(previous_camera_ids)
            time.sleep(5)  # Check every 5 seconds    

    def run(self):
        # Start camera monitoring thread
        thread = threading.Thread(target=self.monitor_cameras)
        thread.start()


# if __name__ == "__main__":
#     manager = CameraManager()
#     manager.run()
