import os
import redis
import cv2
import threading
import subprocess
import numpy as np
import json
import time
from time import sleep, localtime, strftime
from logging_config import *

# Initialize Redis connection
redis_host = 'redis'
redis_port = 6379
r = redis.Redis(host=redis_host, port=redis_port, db=0)
configure_logging()
logger = logging.getLogger("main_logger")
logger.setLevel(logging.INFO)
camera_threads = {}  # Used to store camera_id and corresponding execution control
camera_threads_lock = threading.Lock()  # Thread lock used to protect camera_threads

def get_camera_resolution_ffmpeg(camera_id, camera_url, resolution_event, resolution_data, stop_event, max_retries=3):
    """
    Use FFmpeg to obtain the resolution of the camera and notify the main thread after obtaining it.
    """
    retry_count = 0
    while retry_count < max_retries and not stop_event.is_set():
        try:
            # FFmpeg command, used to obtain resolution
            ffmpeg_cmd = [
                'ffprobe', '-v', 'error', '-select_streams', 'v:0',
                '-show_entries', 'stream=width,height', '-of', 'csv=p=0', '-timeout', '10000000',
                camera_url, '-rtsp_transport', 'tcp'  #TCP protocol and time out TuanDA
            ]
            # Execute the command and get the resolution output
            result = subprocess.run(ffmpeg_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True)
            resolution = result.stdout.strip()            
            if resolution:
                width, height = map(int, resolution.split(','))
                logger.debug(f"[{camera_id}] Resolution detection: {width}x{height} for {camera_url}")
                resolution_data['width'] = width
                resolution_data['height'] = height
                resolution_event.set()  #Notify that the resolution has been obtained
                return
            else:
                logger.warning(f"[{camera_id}] Resolution timeout: {result}")
                logger.warning(f"[{camera_id}] Resolution detection failed, unable to obtain output: try again {retry_count + 1}/{max_retries}")
        except Exception as e:
            logger.warning(f"[{camera_id}] Resolution acquisition failed ({retry_count + 1}/{max_retries}): {e}")
        
        retry_count += 1
        sleep(2)  #Interval between retries
        
    # The number of retries has reached the upper limit, and the setting status is False.

    logger.warning(f"[{camera_id}] Unable to get camera resolution, please check connection settings.")
    r.set(f'camera_{camera_id}_status', 'False')
    resolution_event.set()  # Even if it fails, the event needs to be set to avoid the main thread waiting all the time

def fetch_frame(camera_id, camera_url, camera_fps, stop_event, width, height, max_retries=3):
    """
    Use FFmpeg to obtain image frames, and switch to OpenCV if the maximum number of retries is reached.
    """
    try:
        retry_count = 0
        frame_size = width * height * 3  #Assuming width * height resolution and 3 bytes per pixel (BGR24)
        frame_count = 0
        last_time = time.monotonic()
        while not stop_event.is_set():
            ffmpeg_cmd = [
                'ffmpeg', '-y',  #overwrite if exit
                # '-nostdin', '-probesize', '32', 
                '-rtsp_transport', 'tcp', 
                '-flags', 'low_delay', '-fflags', 'nobuffer',
                '-loglevel', 'error', '-timeout', '10000000', 
                # '-an',               
                #'-hwaccel_output_format', 'cuda', #Decode+Encode on GPU TuanDA
                # '-hwaccel', 'cuda', '-c:v', 'h264_cuvid',
                '-i', camera_url, #'-c:v', 'h264_nvenc', '-preset', 'ultrafast', 
                '-vf', f'fps={camera_fps}', #'-tune', 'zerolatency',
                # '-flush_packets', '1', '-analyzeduration', '32',
                # '-an',              
                '-f', 'rawvideo', 
                '-pix_fmt', 'bgr24',  # Use 'bgr24' format
                'pipe:'
            ]

            try:
                process = subprocess.Popen(ffmpeg_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                logger.debug(f"[{camera_id}] [GPU] Try using FFmpeg to connect to the camera.")
                logger.info(f"[{camera_id}] [GPU] Successfully connected")
            except Exception as e:
                logger.warning(f"[{camera_id}] [GPU] Unable to start FFmpeg process: {e}")
                retry_count += 1
                sleep(2)
                continue

            stderr_thread = threading.Thread(target=read_stderr, args=(camera_id, process.stderr))            
            stderr_thread.start()

            while not stop_event.is_set():
                try:
                    current_time = time.monotonic()
                    raw_frame = process.stdout.read(frame_size)                    
                    if not raw_frame:
                        logger.warning(f"[{camera_id}] [GPU] Unable to read image frames from FFmpeg, the connection may be interrupted.")
                        retry_count += 1
                        if retry_count >= max_retries:
                            # logger.info(f"[{camera_id}] [GPU] Maximum number of retries reached. Switch to using OpenCV.")
                            process.terminate()
                            process.wait()
                            # Call OpenCV method
                            # fetch_frame_opencv(camera_id, camera_url, stop_event)
                            fetch_frame(camera_id, camera_url, camera_fps, stop_event, width, height)
                            return
                        break
                    # logger.info(f"[{camera_id}] camera read frame: {time.monotonic() - current_time}")   
                    # logger.info(f"[{camera_id}] [GPU] retry_count:{retry_count}.")                
                    retry_count = 0  # Reset the number of retries after successfully reading the image
                    # Convert bytes to numpy array for OpenCV
                    frame = np.frombuffer(raw_frame, np.uint8).reshape((height, width, 3))
                    
                    # Save latest image
                    
                    timestamp_str = strftime("%Y%m%d%H%M%S", localtime(current_time + 8 * 3600))
                    _, buffer = cv2.imencode('.jpg', frame)
                    image_data = buffer.tobytes()
                    r.set(f'camera_{camera_id}_latest_frame', image_data)
                    # r.set(f'camera_{camera_id}_latest_frame', pickle.dumps(frame))
                    r.set(f'camera_{camera_id}_status', 'True')
                    r.set(f'camera_{camera_id}_last_timestamp', timestamp_str)
                    r.set(f'camera_{camera_id}_url', camera_url)  
                    # logger.info(f"[{camera_id}] camera process frame: {time.monotonic() - current_time}")                
                    #FPS calcule TuanDA
                    frame_count += 1
                    current_time = time.monotonic()
                    elapsed = current_time - last_time

                    if elapsed >= 60.0:
                        fps = frame_count / elapsed
                        r.set(f"camera_{camera_id}_fps", fps)
                        logger.info(f"[{camera_id}] camera FPS: {fps}")
                        frame_count = 0
                        last_time = current_time    
                except Exception as e:
                    logger.warning(f"[{camera_id}] [GPU] An error occurred: {e}")
                    import traceback
                    traceback.print_exc()
                    retry_count += 1
                    if retry_count >= max_retries:
                        # logger.warning(f"[{camera_id}] [GPU] has reached the maximum number of retries. Switch to using OpenCV.")
                        process.terminate()
                        process.wait()
                        # Call OpenCV method
                        # fetch_frame_opencv(camera_id, camera_url, stop_event)
                        fetch_frame(camera_id, camera_url, camera_fps, stop_event, width, height)
                        return
                    break

            if process:
                process.terminate()
                process.wait()
                logger.info(f"[{camera_id}] [GPU] Stop acquiring image frames.")

    except Exception as e:
        logger.warning(f"[{camera_id}] [GPU] Unhandled exception: {e}")
        import traceback
        traceback.print_exc()
        stop_event.set()

def fetch_frame_cuda(camera_id, camera_url, camera_fps, stop_event, width, height, max_retries=3):
    """
    Use FFmpeg Cuda to obtain image frames, and switch to OpenCV if the maximum number of retries is reached.
    """
    try:
        retry_count = 0
        frame_size = width * height * 3 // 2  #Assuming width * height resolution and 3 bytes per pixel (yuv420p)
        frame_count = 0
        last_time = time.monotonic()
        while not stop_event.is_set():
            ffmpeg_cmd = [
                'ffmpeg', '-y', 
                '-rtsp_transport', 'tcp', 
                '-flags', 'low_delay', '-fflags', 'nobuffer',
                '-loglevel', 'warning', '-timeout', '1000000',                 
                '-hwaccel_output_format', 'cuvid', 
                '-hwaccel', 'cuda', 
                '-c:v', 'h264_cuvid',
                '-i', camera_url, 
                '-flush_packets', '1', '-analyzeduration', '32', '-probesize', '32',
                '-f', 'rawvideo',
                # '-c:v', 'h264_nvenc',
                # '-vframes', '1',
                '-pix_fmt', 'yuv420p',
                'pipe:'
            ]

            try:
                process = subprocess.Popen(ffmpeg_cmd, stdout=subprocess.PIPE, stderr=subprocess.PIPE)
                logger.info(f"[{camera_id}] [GPU] Try using FFmpeg to connect to the camera.")
                logger.info(f"[{camera_id}] [GPU] Successfully connected")
            except Exception as e:
                logger.warning(f"[{camera_id}] [GPU] Unable to start FFmpeg process: {e}")
                retry_count += 1
                sleep(2)
                continue

            stderr_thread = threading.Thread(target=read_stderr, args=(camera_id, process.stderr))
            
            stderr_thread.start()

            while not stop_event.is_set():
                try:
                    current_time = time.monotonic()
                    raw_frame = process.stdout.read(frame_size)                    
                    if not raw_frame:
                        logger.warning(f"[{camera_id}] [GPU] Unable to read image frames from FFmpeg, the connection may be interrupted.")
                        retry_count += 1
                        if retry_count >= max_retries:
                            logger.warning(f"[{camera_id}] [GPU] Maximum number of retries reached. Switch to using OpenCV.")
                            process.terminate()
                            process.wait()
                            # Call OpenCV method
                            # fetch_frame_opencv(camera_id, camera_url, stop_event)
                            return
                        break
                    logger.info(f"[{camera_id}] camera read frame: {time.monotonic() - current_time}")   
                    # logger.info(f"[{camera_id}] [GPU] retry_count:{retry_count}.")                
                    retry_count = 0  # Reset the number of retries after successfully reading the image
                    # Convert bytes to numpy array for OpenCV
                    yuv = np.frombuffer(raw_frame, dtype=np.uint8).reshape((height*3//2, width))
                    frame = cv2.cvtColor(yuv, cv2.COLOR_YUV2BGR_I420)
                    # Save latest image
                    
                    timestamp_str = strftime("%Y%m%d%H%M%S", localtime(current_time + 8 * 3600))
                    _, buffer = cv2.imencode('.jpg', frame)
                    image_data = buffer.tobytes()
                    r.set(f'camera_{camera_id}_latest_frame', image_data)
                    r.set(f'camera_{camera_id}_status', 'True')
                    r.set(f'camera_{camera_id}_last_timestamp', timestamp_str)
                    r.set(f'camera_{camera_id}_url', camera_url)  
                    logger.info(f"[{camera_id}] camera process frame: {time.monotonic() - current_time}")                
                    #FPS calcule TuanDA
                    frame_count += 1
                    current_time = time.monotonic()
                    elapsed = current_time - last_time

                    if elapsed >= 10.0:
                        fps = frame_count / elapsed
                        r.set(f"camera_{camera_id}_fps", fps)
                        # logger.info(f"[{camera_id}] camera FPS: {fps}")
                        frame_count = 0
                        last_time = current_time    
                except Exception as e:
                    logger.warning(f"[{camera_id}] [GPU] An error occurred: {e}")
                    import traceback
                    traceback.print_exc()
                    retry_count += 1
                    if retry_count >= max_retries:
                        logger.warning(f"[{camera_id}] [GPU] has reached the maximum number of retries. Switch to using OpenCV.")
                        process.terminate()
                        process.wait()
                        # Call OpenCV method
                        # fetch_frame_opencv(camera_id, camera_url, stop_event)
                        return
                    break

            if process:
                process.terminate()
                process.wait()
                logger.info(f"[{camera_id}] [GPU] Stop acquiring image frames.")

    except Exception as e:
        logger.warning(f"[{camera_id}] [GPU] Unhandled exception: {e}")
        import traceback
        traceback.print_exc()
        stop_event.set()

def fetch_frame_opencv(camera_id, camera_url, stop_event):
    """
    Use OpenCV to read images directly from the camera.
    """
    logger.info(f"[{camera_id}] [CPU] Connect to the camera using OpenCV. ")
    if camera_url is None:
        logger.warning(f"[{camera_id}] Camera is None url. May be is not active")
        camera_threads.pop(camera_id)
        return
    cap = cv2.VideoCapture(camera_url)

    frame_count = 0
    last_time = time.monotonic()
    if not cap.isOpened():
        logger.warning(f"[{camera_id}] [CPU] Unable to open camera using OpenCV.")
        r.set(f'camera_{camera_id}_status', 'False')
        return

    while not stop_event.is_set():
        ret, frame = cap.read()
        if not ret:
            logger.warning(f"[{camera_id}] [CPU] Failed to read image using OpenCV, try to reconnect.")
            cap.release()
            time.sleep(2)
            cap = cv2.VideoCapture(camera_url)
            if not cap.isOpened():
                logger.warning(f"[{camera_id}] [CPU] Unable to reconnect camera.")
                r.set(f'camera_{camera_id}_status', 'False')
                stop_event.set()
                break
            continue

        # Save latest image
        current_time = time.monotonic()
        timestamp_str = strftime("%Y%m%d%H%M%S", localtime(current_time + 8 * 3600))
        _, buffer = cv2.imencode('.jpg', frame)
        image_data = buffer.tobytes()
        r.set(f'camera_{camera_id}_latest_frame', image_data)
        r.set(f'camera_{camera_id}_status', 'True')
        r.set(f'camera_{camera_id}_last_timestamp', timestamp_str)
        r.set(f'camera_{camera_id}_url', camera_url)

        #FPS calcule TuanDA
        frame_count += 1
        current_time = time.monotonic()
        elapsed = current_time - last_time

        if elapsed >= 10.0:
            fps = frame_count / elapsed
            r.set(f"camera_{camera_id}_fps", fps)
            print(f"[{camera_id}] camera FPS: {fps}")
            frame_count = 0
            last_time = current_time
    cap.release()
    logger.info(f"[{camera_id}] [CPU] Stop using OpenCV to acquire images.")

def read_stderr(camera_id, stderr_pipe):
    """
    Continuously reads FFmpeg's error output and prints it.
    """
    for line in iter(stderr_pipe.readline, b''):
        print(f"[{camera_id}] [GPU] FFmpeg error output：{line.decode('utf-8').strip()}")

def get_resolution_and_start_fetching(camera_id, camera_url, camera_fps, stop_event):
    """
    Get the camera resolution and start acquiring images.
    """
    try:
        # If stop_event is set, clear it
        if stop_event.is_set():
            stop_event.clear()
        resolution_event = threading.Event()
        resolution_data = {}
        if camera_url is None:
            logger.warning(f"[{camera_id}] Camera is None url. May be is not active")
            camera_threads.pop(camera_id)
            return
        # Start the thread to get the resolution
        resolution_thread = threading.Thread(
            target=get_camera_resolution_ffmpeg,
            args=(camera_id, camera_url, resolution_event, resolution_data, stop_event)
        )
        resolution_thread.start()
        # Wait for resolution acquisition or stop event
        while not resolution_event.is_set() and not stop_event.is_set():
            time.sleep(0.1)
        # If the resolution is successfully obtained, start the image acquisition thread
        if 'width' in resolution_data and 'height' in resolution_data:
            width = resolution_data['width']
            height = resolution_data['height']
            fetch_thread = threading.Thread(
                target=fetch_frame, args=(camera_id, camera_url, camera_fps, stop_event, width, height)
            )
            fetch_thread.start()
            with camera_threads_lock:
                camera_threads[camera_id]['fetch_thread'] = fetch_thread
            fetch_thread.join()
        else:
            # logger.info(f"[{camera_id}] Unable to obtain resolution, use OpenCV direct connection instead.")
            # Call OpenCV method
            # fetch_frame_opencv(camera_id, camera_url, stop_event)
            get_resolution_and_start_fetching(camera_id, camera_url, camera_fps, stop_event)
    except Exception as e:
        logger.warning(f"[{camera_id}] An error occurred while getting resolution: {e}")
        import traceback
        traceback.print_exc()
        # Call OpenCV method
        # fetch_frame_opencv(camera_id, camera_url, stop_event)
        get_resolution_and_start_fetching(camera_id, camera_url, camera_fps, stop_event)

def manage_camera_threads(current_camera_data):
    """
    Manage the camera thread, start new cameras, stop removed cameras, and handle URL changes.
    """
    global camera_threads

    with camera_threads_lock:
        existing_camera_ids = set(camera_threads.keys())
        new_camera_ids = set(current_camera_data.keys())

        # Find the camera that needs to be stopped (in the old list, but not in the new list)
        cameras_to_stop = existing_camera_ids - new_camera_ids
        # Find the camera that needs to be started (in the new list, but not in the old list)
        cameras_to_start = new_camera_ids - existing_camera_ids

        # Stop camera threads that are no longer needed
        for camera_id in cameras_to_stop:
            stop_event = camera_threads[camera_id]['stop_event']
            stop_event.set()
            del camera_threads[camera_id]
            logger.info(f"[{camera_id}] Camera thread stopped.")

        # Handle URL updates or start a new camera thread
        for camera_id in new_camera_ids.union(existing_camera_ids):
            new_url = current_camera_data.get(camera_id)
            camera_info = r.get(f"camera_{camera_id}_info")
            if  camera_info is not None:
                camera_info = json.loads(camera_info.decode("utf-8"))
                new_fps = camera_info.get('config').get('fps') if camera_info.get('config').get('fps') is not None else 20
            else:
                new_fps = 20    
            if camera_id in camera_threads:
                old_url = camera_threads[camera_id]['camera_url']
                old_fps = camera_threads[camera_id]['camera_fps']
                # Check if the URL has changed
                if new_url != old_url or  new_fps != old_fps:
                    print(f"[{camera_id}] URL or FPS has changed, restart the camera thread.")
                    stop_event = camera_threads[camera_id]['stop_event']
                    stop_event.set()  # Stop old thread
                    del camera_threads[camera_id]

                    # Start a new thread
                    stop_event = threading.Event()
                    camera_thread = threading.Thread(
                        target=get_resolution_and_start_fetching,
                        args=(camera_id, new_url, new_fps, stop_event)
                    )
                    camera_thread.start()
                    camera_threads[camera_id] = {
                        'thread': camera_thread,
                        'stop_event': stop_event,
                        'camera_url': new_url,
                        'camera_fps': new_fps,
                        'retry_count': 0
                    }
                    logger.info(f"[{camera_id}] Camera thread restarted with new config.")
            else:
                # Start a new camera thread
                logger.info(f"[{camera_id}] Start a new camera thread.")
                stop_event = threading.Event()
                camera_thread = threading.Thread(
                    target=get_resolution_and_start_fetching,
                    args=(camera_id, new_url, new_fps, stop_event)
                )
                camera_thread.start()
                camera_threads[camera_id] = {
                    'thread': camera_thread,
                    'stop_event': stop_event,
                    'camera_url': new_url,
                    'camera_fps': new_fps,
                    'retry_count': 0
                }
                logger.info(f"[{camera_id}] Camera thread started.")

def monitor_cameras():
    """
    Monitor camera inventory updates and manage camera threads.
    """
    worker_id = os.getenv('WORKER_ID')
    if worker_id is None:
        raise ValueError("WORKER_ID Environment variable not set.")
    else:
        logger.info(f"WORKER_ID: {worker_id}")

    worker_key = f'worker_{worker_id}_urls'
    pubsub = r.pubsub()
    pubsub.subscribe(f'{worker_key}_update')

    logger.info(f"Monitoring updates for worker {worker_id}.")
    camera_data = get_camera_data(worker_key)
    manage_camera_threads(camera_data)

    last_camera_data = camera_data.copy()  # Record the last camera list

    while True:
        # Set message timeout to avoid blocking the main program
        message = pubsub.get_message(ignore_subscribe_messages=True, timeout=1)

        if message:
            # No matter what message is received, re-fetch the camera list and compare if there are any changes
            current_camera_data = get_camera_data(worker_key)
            if current_camera_data != last_camera_data:
                logger.info("Detected update event. Refreshing camera list.")
                manage_camera_threads(current_camera_data)
                last_camera_data = current_camera_data.copy()
            else:
                # There is no change to the camera list, no action is taken
                pass

        # Add a short wait to prevent the process from being overly occupied
        time.sleep(0.5)

def get_camera_data(worker_key):
    """
    Get a list of camera URLs from Redis.
    """
    camera_urls = r.smembers(worker_key)
    # print(f"[Camera data: {camera_urls}.")
    camera_data = {}

    for url in camera_urls:
        url_parts = url.decode('utf-8').split('|')
        camera_id, camera_url = url_parts[0], url_parts[1]
        camera_data[camera_id] = camera_url
    # print(f'camera_data:{camera_data}')    
    return camera_data

def monitor_camera_threads():
    """
    Monitor camera threads and try to restart stopped threads.
    """
    while True:
        with camera_threads_lock:
            for camera_id, thread_info in list(camera_threads.items()):
                thread = thread_info['thread']
                stop_event = thread_info['stop_event']

                if not thread.is_alive():
                    logger.info(f"[{camera_id}] The thread has stopped. Try restarting.")
                    # Clear stop event
                    stop_event.clear()
                    #Create new thread
                    new_thread = threading.Thread(
                        target=get_resolution_and_start_fetching,
                        args=(camera_id, thread_info['camera_url'], thread_info['camera_fps'], stop_event)
                    )
                    new_thread.start()
                    camera_threads[camera_id]['thread'] = new_thread
        time.sleep(5)  # Check every 5 seconds

if __name__ == "__main__":
    threading.Thread(target=monitor_camera_threads, daemon=True).start()
    monitor_cameras()