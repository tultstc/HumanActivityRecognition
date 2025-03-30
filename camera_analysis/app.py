import logging
import asyncio
import time
import os

import cv2
import redis
import json

from ultralytics import YOLO  # Import Ultralytics Package
from ultralytics import RTDETR # Import Ultralytics Package
from ultralytics import solutions  # Import Ultralytics Package
# local kit
from ApiService import ApiService
from PlcService import PlcService
from image_storage import ImageStorage
from logging_config import *
from solution.analysis_model_zone import analysis_zone
from solution.analysis_model_pose import analysis_pose
class MainApp:
    def __init__(self):

        configure_logging()  # Setup log
        self.logger = logging.getLogger("main_logger")
        self.logger.setLevel(logging.INFO)
        self.lic_check = LicCheck()
        self.api_service = ApiService(base_url=os.getenv("WEB_SERVICE_URL"))
        self.plc_servive = PlcService(plc_ip=os.getenv("PLC_IP"))
        self.last_sent_timestamps = {}
        self.SLEEP_INTERVAL = 0.1  # Set a reasonable sleep interval
        self.camera_frame_count = {}
        self.camera_alarm = {}


        self.redis_host = "redis"
        self.redis_port = 6379
        self.r = redis.Redis(host=self.redis_host, port=self.redis_port, db=0)
        self.image_storage = ImageStorage(self.r)

        self.init_dirs()
        self.init_models()

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

    # TuanDA
    def init_models(self):
        start_time = time.monotonic()
        # Load the model from database and initialize the annotator
        self.models = {}  # Dictionary used to store models
        # Get model list
        check = True
        while check:
            self.model_list = self.api_service.get_model_list()
            if not self.model_list:
                self.logger.warning("No model for init. Check database again!")
            else:
                check = False
        for model in self.model_list:
            model_path = model.get("url")
            model_id = model.get("id")
            # Load model using ultralytics YOLO
            match model_id: #Detect
                case 1 | 6 | 7 | 8 | 9 | 10:
                    self.models[model_id] = {
                        'path':  YOLO(model_path),
                        'config': model.get("config"),
                    }                
                    # zone = [(20,400), (600,400), (600,360), (400,300), (200,300), (20,360)]
                    # zone = [(20,700), (1450,700), (1200,440), (120,440)]
                    # self.models[model_id] = ZoneDetect(
                    #     show=True,
                    #     region=zone,
                    #     model=model_path,
                    #     classes=[0],
                    #     verbose=False,
                    # )
                case 5:
                    self.models[model_id] = {
                        'path':  RTDETR(model_path),
                        'config': model.get("config"),
                    }                
                case 2: #Pose
                    self.models[model_id] = {
                        'path':  YOLO(model_path),
                        'config': model.get("config"),
                    }
                case 3:
                    # line_points = [(750, 500),(1200, 500),(1200, 530),(750,530)]
                    line_points = [(0, 330), (1700, 330)]
                    # self.models[model_id] = {
                    #     'path':  solutions.ObjectCounter(
                    #     show=True,
                    #     region=line_points,
                    #     model=model_path,
                    #     classes=[0],
                    #     verbose=False,
                    # ),
                    #     'config': model.get("config"),
                    # }
                case 4:
                    self.models[model_id] = {
                        'path':  YOLO(model_path),
                        'config': model.get("config"),
                    }
            self.logger.debug(f"Model {model_id} loaded from {model_path}")
        self.logger.info(f"Check models initialized in {time.monotonic() - start_time:.2f} seconds")

    async def fetch_camera_status(self):
        # start_time = time.monotonic()
        loop = asyncio.get_event_loop()
        status = await loop.run_in_executor(None, self.image_storage.fetch_camera_status)
        # self.logger.debug(f"Snapshot for camera status fetched in {time.monotonic() - start_time:.2f} seconds")
        return status

    async def fetch_snapshot(self, camera_id):       
        """Get the latest image of the specified camera from Redis"""
        # start_time = time.monotonic()
        redis_key = f"camera_{camera_id}_latest_frame"
        loop = asyncio.get_event_loop()
        image = await loop.run_in_executor(None, self.image_storage.fetch_image, redis_key)
        # image = await self.image_storage.fetch_image(redis_key)
        # self.logger.debug(f"Snapshot for camera {camera_id} fetched in {time.monotonic() - start_time:.2f} seconds")
        return image

    async def process_camera(self, camera_id, camera_info, images_batches):
        img = await self.fetch_snapshot(camera_id)
        if img is not None:
            # self.logger.info(f"Image from camera {camera_id} ready for processing")
            model_id = camera_info.get("model_id")
            if model_id in self.models:
                # Add images and information to corresponding batches based on model type
                if model_id not in images_batches: 
                    images_batches[model_id] = {
                        'images': [],
                        'camera_info': []
                    }
                images_batches[model_id]['images'].append(img)
                images_batches[model_id]['camera_info'].append((camera_id, camera_info))
                #FPS calcule TuanDA
                self.camera_frame_count[camera_id]['count'] += 1
                current_time = time.monotonic()
                elapsed = current_time - self.camera_frame_count[camera_id]['last_time']
                if elapsed >= 10.0:
                    fps = self.camera_frame_count[camera_id]['count'] / elapsed
                    self.camera_frame_count[camera_id]['count'] = 0
                    self.camera_frame_count[camera_id]['last_time'] = current_time
                    self.logger.debug(f"Camera {camera_id} FPS: {fps}")
            else:
                self.logger.warning(f"No valid recognition model for camera {camera_id}")
        else:
            self.logger.warning(f"No image fetched for camera {camera_id}")

    def call_model_batch(self, model_id, batch_data):
        """Process a batch of images using the specified model"""
        start_time = time.monotonic()

        model = self.models[model_id]['path']
        model_config = self.models[model_id]['config']
        images_batch = batch_data['images']
        camera_info_batch = batch_data['camera_info']
        # TuanDA Chia loai model de xu ly model1 = Tracking, model2 = Detector,Model3 = Counter...
        match model_id:
            case 1 | 5 | 6 | 7 | 8 | 9 | 10: #Detect           
                results, detection_flags = analysis_zone.process_model(self,batch_data,model,model_config)
                for i, detections in enumerate(results):
                    camera_id, camera_info = camera_info_batch[i]
                    # annotated_image = detections.plot()
                    annotated_image = detections.orig_img
                    # Save images and send notifications
                    self.save_and_notify(annotated_image, camera_info, detection_flags[i])
            case 2: #Pose
                results, detection_flags = analysis_pose.process_model(self,batch_data,model,model_config)
                for i, detections in enumerate(results):
                    camera_id, camera_info = camera_info_batch[i]
                    # annotated_image = detections.plot()
                    annotated_image = detections.orig_img
                    # Save images and send notifications
                    self.save_and_notify(annotated_image, camera_info, detection_flags[i])    
            case 3: #count
                annotated_image = model.count(images_batch[0])
                camera_id, camera_info = camera_info_batch[0]             
                # Save images and send notifications
                self.save_and_notify(annotated_image, camera_info, False)  
            case 4: #tracking
                results = model.track(images_batch[0], conf=model_config.get("conf"), classes=model_config.get("label_conf"), verbose=False)
                annotated_image = results[0].plot()
                camera_id, camera_info = camera_info_batch[0]             
                # Save images and send notifications
                self.save_and_notify(annotated_image, camera_info, False)

        # self.logger.info(f"Batch model {model_id} ({len(images_batch)} images) processing completed in {time.monotonic() - start_time:.2f} seconds")
    
    def save_and_notify( self, annotated_image, camera_info, detection_flag):
        
        start_time = time.monotonic()
        camera_id = camera_info.get("id")
        redis_key = f"camera_{camera_id}_boxed_image"
        self.image_storage.save_image(redis_key, annotated_image)
        # self.image_storage.save_image_raw(redis_key, annotated_image)
        # self.logger.debug(f"Camera {camera_id} save and notify completed in {time.monotonic() - start_time:.2f} seconds")

        #Caculate Alarm
        maxinframe =  2 if camera_info.get("config").get("maxinframe") is None else camera_info.get("config").get("maxinframe")
        maxoutframe =  5 if camera_info.get("config").get("maxoutframe") is None else camera_info.get("config").get("maxoutframe")
        maxsendframe =  10 if camera_info.get("config").get("maxsendframe") is None else camera_info.get("config").get("maxsendframe")
        addresson = "V100.0" if camera_info.get("config").get("addresson") is None else camera_info.get("config").get("addresson")
        addressoff = "V100.1" if camera_info.get("config").get("addressoff") is None else camera_info.get("config").get("addressoff")
        self.camera_alarm[camera_id]['time'] = start_time
        if detection_flag:
            self.camera_alarm[camera_id]['inframe'] +=1
            if self.camera_alarm[camera_id]['status'] == False and self.camera_alarm[camera_id]['inframe'] >= maxinframe:
                #Set and send alarm here
                self.camera_alarm[camera_id]['status'] = True
                self.camera_alarm[camera_id]['outframe'] = 0
                alarm_img_path = os.path.join(self.ALARM_SAVE_DIR, f"{camera_id}_{start_time}.jpg")
                self.camera_alarm[camera_id]['url'] = alarm_img_path
                self.camera_alarm[camera_id]['camera_id']=camera_id
                cv2.imwrite(alarm_img_path, annotated_image)
                self.logger.debug(f"[{camera_id}] Alarm image saved to {alarm_img_path} in {time.monotonic() - start_time:.2f} seconds")
                #Send web
                self.api_service.send_web_notify_message_v2(self.camera_alarm[camera_id])
                #Send PLC
                self.logger.info(f"[{camera_id}] Alarm PLC sent on 1st - {self.camera_alarm[camera_id]['inframe']}")
                self.plc_servive.write_alarm_on(addresson)
                              
            elif self.camera_alarm[camera_id]['status'] and (self.camera_alarm[camera_id]['inframe'] >= maxinframe) and (self.camera_alarm[camera_id]['inframe'] % maxsendframe == 0):
                ##Send PLC
                self.logger.info(f"[{camera_id}] Alarm PLC sent on 2nd - {self.camera_alarm[camera_id]['inframe']}")
                self.camera_alarm[camera_id]['outframe'] = 0
                if self.camera_alarm[camera_id]['inframe'] > 10000:
                    self.camera_alarm[camera_id]['inframe'] = maxinframe  
                self.plc_servive.write_alarm_on(addresson)   
        else:
            self.camera_alarm[camera_id]['outframe'] += 1
            if self.camera_alarm[camera_id]['status'] == True and self.camera_alarm[camera_id]['outframe'] >= maxoutframe:
                #Send PLC
                self.logger.info(f"[{camera_id}] Alarm PLC sent off - {self.camera_alarm[camera_id]['outframe']}")
                self.plc_servive.write_alarm_off(addressoff) 
                # Send web update
                self.api_service.update_web_notify_message(self.camera_alarm[camera_id])
                self.camera_alarm[camera_id]['status'] = False
                self.camera_alarm[camera_id]['inframe'] = 0
                self.camera_alarm[camera_id]['outframe'] = 0
                self.camera_alarm[camera_id]['url'] = ""
            else:
                if self.camera_alarm[camera_id]['outframe'] > 5000:
                    self.camera_alarm[camera_id]['outframe'] = 0  
                
    async def main_loop(self):

        check_time = time.monotonic()       
        lic_time =  check_time
        lic_info = True
        camera_status = await self.fetch_camera_status()
        if not camera_status:
            self.logger.warning("No camera status received")

        for camera_id, status in camera_status.items():   
            self.camera_frame_count[int(camera_id)] = {
                'count': 0,
                'last_time': check_time,
            }
            self.camera_alarm[int(camera_id)] = {
                'time': check_time,
                'status': False,
                'inframe': 0,
                'outframe': 0,
                'url': ''
            }
        while True:
            start_time = time.monotonic()
            # Initialize batch dictionary
            images_batches = {}

            # Create processing tasks for each camera
            tasks = []
            for camera_id, status in camera_status.items():
                if status["alive"] == "True" and status["last_image_timestamp"] != 'unknown' and status["camera_info"]:
                    camera_info = json.loads(status["camera_info"])
                    if camera_info:
                        tasks.append(asyncio.create_task(self.process_camera(int(camera_id), camera_info, images_batches)))
            if tasks and lic_info:
                # Perform processing tasks for all cameras asynchronously
                await asyncio.gather(*tasks)
                # self.logger.info(f"Processing camera is completed, taking {time.monotonic() - start_time:.2f} seconds")
                # After all images are collected, batch inference is performed for each model type
                # model_tasks = []
                for model_id, batch_data in images_batches.items():
                    # model_tasks.append(asyncio.create_task(self.call_model_batch(model_id, batch_data)))
                    self.call_model_batch(model_id, batch_data)
                # await asyncio.gather(*model_tasks)
            elif (not tasks) and lic_info:
                self.logger.warning("No processing camera")

            # self.logger.info("Check camera status...")
            elapsed = start_time - check_time
            if elapsed >= 5.0 and lic_info:
                self.logger.info(f"Processing Analysis is about {elapsed_time:.2f} seconds")
                check_time = start_time
                camera_status = await self.fetch_camera_status()
                if not camera_status:
                    self.logger.warning("No camera status received")
                    await asyncio.sleep(self.SLEEP_INTERVAL)
                    continue  
            #lic check
            elapsed = start_time - lic_time
            if elapsed >= 60.0:
                lic_time = start_time
                lic_info = self.lic_check.check(False)['status']
                if not lic_info: self.logger.error(f"Check system status: {lic_info}")         
            # Dynamically adjust sleep time
            elapsed_time = time.monotonic() - start_time
            adjusted_sleep = max(0.001, self.SLEEP_INTERVAL - elapsed_time)
            await asyncio.sleep(adjusted_sleep)

if __name__ == "__main__":
    app = MainApp()
    asyncio.run(app.main_loop())