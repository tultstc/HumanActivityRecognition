import redis
import os
import json
# Initialize Redis connection
def init_redis():
    redis_host = 'redis'
    redis_port = 6379
    return redis.Redis(host=redis_host, port=redis_port, db=0)

class CameraSnapFetcher:
    def __init__(self, redis_connection):
        self.redis = redis_connection

    def get_snap_by_url(self, camera_url):
        key = f"camera_{camera_url}_latest_frame"
        path = self.redis.lrange(key, -1, -1)
        if not path:
            return None
        path = path[0].decode('utf-8')
        if os.path.exists(path):
            return path
        return None

def get_all_camera_status(r):
    status = {}
    for key in r.keys("camera_*_status"):
        camera_id = key.decode().split('_')[1]
        if r.exists(f'camera_{camera_id}_status'):
            camera_status = r.get(key)
            last_timestamp = r.get(f'camera_{camera_id}_last_timestamp')
            #Config TuanDA
            camera_info = r.get(f'camera_{camera_id}_info')
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
