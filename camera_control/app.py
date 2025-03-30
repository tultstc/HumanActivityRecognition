from flask import Flask, request, jsonify, send_file, render_template, Response
from flask_restx import Api, Resource, fields
from redis_utils import init_redis, get_all_camera_status
from camera_manager import CameraManager
import time
import base64
from flask_cors import CORS
import os
from urllib.parse import unquote
from werkzeug.utils import safe_join
import logging
from imutils import build_montages
import numpy as np
import cv2
from logging_config import *

app = Flask(__name__)
api = Api(app)
CORS(app)
# Allow access from specific domains
# allowed_origins = ["https://pytest.intemotech.com"]

# cors = CORS(app, resources={
#     r"/*": {"origins": allowed_origins}
# })

#Config Log
configure_logging()
logger = logging.getLogger("main_logger")
logger.setLevel(logging.INFO)
# initialization Redis
r = init_redis()
manager = CameraManager()
manager.run()
lic_check = LicCheck()
camera_model = api.model('Camera', {
    'camera_id': fields.String(required=True, description='The camera identifier'),
    'url': fields.String(required=True, description='The URL of the camera stream')
})

camera_ids_model = api.model('CameraIds', {
    'camera_ids': fields.List(fields.String, required=True, description='List of camera identifiers')
})

polygon_model = api.model('Polygon', {
    'points': fields.List(fields.Nested(api.model('Point', {
        'x': fields.Float(required=True, description='X coordinate of the point'),
        'y': fields.Float(required=True, description='Y coordinate of the point'),
    }))),
    'camera_id': fields.String(required=True, description='Camera ID associated with this polygon')
})
@app.route('/check')
def check():
    status = lic_check.check(False)
    return status, 200
    
@api.route('/camera_status')
class CameraStatus(Resource):
    def get(self):
        status = get_all_camera_status(r)
        return status, 200

@app.route('/get_snapshot/<camera_id>')
def get_latest_frame(camera_id):
    image_data = r.get(f'camera_{camera_id}_latest_frame')
    if image_data:
        return Response(image_data, mimetype='image/jpeg')
    else:
        return send_file('no_single.jpg', mimetype='image/jpeg')

@app.route('/image/<path:image_path>')
def get_image(image_path):
    try:
        return send_file(image_path, mimetype='image/jpeg')
    except FileNotFoundError:
        return send_file('no_single.jpg', mimetype='image/jpeg')

def generate_frames(camera_id):
    while True:
        frame_key = f'camera_{camera_id}_latest_frame'
        frame_data = r.get(frame_key)
        if frame_data:
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame_data + b'\r\n')
        else:
            print(f'generate_frames {camera_id} is null')
            break
        time.sleep(0.05)

def generate_recognized_frames(camera_id):
    # count = 0
    while True:
        frame_key = f'camera_{camera_id}_boxed_image'
        frame_data = r.get(frame_key)
        if frame_data: 
            # count = count + 1           
            # logger.info(f'generate_recognized_frames {camera_id} is {count}')
            yield (b'--frame\r\n'
                   b'Content-Type: image/jpeg\r\n\r\n' + frame_data + b'\r\n')
        else:
            logger.info(f'generate_recognized_frames {camera_id} is null')
            break
        time.sleep(0.05)

def generate_recognized_multi_frames(keys, row, column):
    while True:
        # start_time = time.monotonic()
        images = []
        for camera_id in keys:
            frame_key = f'camera_{camera_id}_boxed_image'
            frame_data = r.get(frame_key)
            if frame_data:
                # Decode the byte stream to an image array
                nparr = np.frombuffer(frame_data, np.uint8)  # Convert bytes to NumPy array
                image = cv2.imdecode(nparr, cv2.IMREAD_COLOR)  # Decode image          
                images.append(image)               
            else:
                # logger.info(f'generate_recognized_frames {camera_id} is null')
                continue
        # logger.info(f"Processing 1 taking {time.monotonic() - start_time:.2f} seconds")    
        # create montage
        # build_montages(images_list, (width,height), (column,row))
        montages = build_montages(images, (1024,768), (column,row)) # return numpy array        
        _, buffer = cv2.imencode('.jpg', montages[0])
        montage_image = buffer.tobytes()
        # logger.info(f"Processing 2 taking {time.monotonic() - start_time:.2f} seconds")

        yield (b'--frame\r\n'
                b'Content-Type: image/jpeg\r\n\r\n' +montage_image + b'\r\n')
        time.sleep(0.05)
        # logger.info(f"Processing 3 taking {time.monotonic() - start_time:.2f} seconds")    
 
# Identify streaming routes (via redis)
@app.route('/recognized_stream/<ID>')
def recognized_stream(ID):
    return Response(generate_recognized_frames(ID), mimetype='multipart/x-mixed-replace; boundary=frame')

# Identify streaming routes (via redis)
@app.route('/recognized_stream', methods=['GET'])
def recognized_stream_multi():
    # Get video keys from request parameter
    keys_param = request.args.get('camera_ids')  
    row = int(request.args.get('row', 3))
    column = int(request.args.get('column', 4))
    if not keys_param:
        return {"error": "No video keys provided"}, 400
    # Split keys by comma
    keys = keys_param.split(',')
    # Stream videos with multipart/x-mixed-replace
    return Response(generate_recognized_multi_frames(keys, row, column), mimetype='multipart/x-mixed-replace; boundary=frame')

# Streaming Router
@app.route('/get_stream/<int:ID>')
def get_stream(ID):
    return Response(generate_frames(ID), mimetype='multipart/x-mixed-replace; boundary=frame')

# Snapshot UI routing
@app.route('/snapshot_ui/<ID>')
def snapshot_ui(ID):
    image_key = f'camera_{ID}_latest_frame'
    image_data = r.get(image_key)
    if image_data:
        # Encode the image as Base64 and pass it to the template
        encoded_image = base64.b64encode(image_data).decode('utf-8')
        return render_template('snapshot_ui.html', camera_id=ID, image_data=encoded_image)
    else:
        return send_file('no_single.jpg', mimetype='image/jpeg')

# Display image GET method
@app.route('/showimage/<path:image_path>', methods=['GET'])
def show_image_get(image_path):
    # Decode path of URL
    image_path = unquote(image_path)
    
    # remove prefix
    prefix = 'logs/annotated_images/'
    if image_path.startswith(prefix):
        image_path = image_path[len(prefix):]
    
    # Set base directory
    base_dir = os.path.join(app.root_path, 'logs', 'annotated_images')
    
    # Combine the complete path
    image_full_path = safe_join(base_dir, image_path)
    
    print(f"Requested image path: {image_full_path}")
    
    # Confirm image exists
    if not os.path.exists(image_full_path):
        print(f"Image not found at path: {image_full_path}")
        return jsonify({'error': 'Image not found', 'path': image_full_path}), 404

    try:
        # Return to picture
        return send_file(image_full_path, mimetype='image/jpeg')
    except Exception as e:
        return jsonify({'error': str(e)}), 500
    
@app.route('/delete-event-image/<path:image_path>', methods=['POST'])
def delete_event_image(image_path):
    try:
        image_path = unquote(image_path)
        prefix = 'logs/alarm_images/'
        if image_path.startswith(prefix):
            image_path = image_path[len(prefix):]
        base_dir = os.path.join(app.root_path, 'logs', 'alarm_images')
        image_full_path = safe_join(base_dir, image_path)
        logger.info(f"Attempting to delete image at path: {image_full_path}")
        
        if not os.path.exists(image_full_path):
            logger.warning(f"Image not found at path: {image_full_path}")
            return jsonify({'error': 'Image not found', 'path': image_full_path}), 404

        os.remove(image_full_path)
        logger.info(f"Successfully deleted image at path: {image_full_path}")
        
        return jsonify({'success': True, 'message': 'Image deleted successfully'})
        
    except Exception as e:
        logger.error(f"Error deleting image: {str(e)}")
        return jsonify({'error': str(e)}), 500

@app.route('/delete-all-images', methods=['DELETE'])
def delete_all_images():
    try:
        alarm_images_dir = os.path.join(app.root_path, 'logs', 'alarm_images')

        if not os.path.exists(alarm_images_dir):
            logger.warning(f"Directory not found: {alarm_images_dir}")
            return jsonify({'error': 'Directory not found', 'path': alarm_images_dir}), 404

        for filename in os.listdir(alarm_images_dir):
            file_path = os.path.join(alarm_images_dir, filename)
            if os.path.isfile(file_path):
                os.remove(file_path)
                logger.info(f"Deleted file: {file_path}")
        
        logger.info(f"Successfully deleted all files in directory: {alarm_images_dir}")
        return jsonify({'success': True, 'message': 'All images deleted successfully'})

    except Exception as e:
        logger.error(f"Error deleting all images: {str(e)}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5000)
    # app.run()
