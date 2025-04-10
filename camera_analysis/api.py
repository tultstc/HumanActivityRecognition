import base64
import os
import json
import re
import subprocess
import tempfile
import threading
import signal
import uuid
import psutil
import time
import mmengine
import cv2
from pathlib import Path

from combine_pkl import combine_pkl_files
from tools.data.skeleton.ntu_pose_extraction import NTUPoseExtractor
import torch
from flask import Flask, request, jsonify, send_from_directory, Response, stream_with_context
from flask_cors import CORS
from werkzeug.utils import secure_filename

app = Flask(__name__)
CORS(app)
app.secret_key = "posec3d"

DATA_DIR = os.environ.get('DATA_DIR', './')
LABELS_FILE = os.path.join(DATA_DIR, 'labels_map.txt')
running_processes = {}
training_jobs = {}


os.makedirs(DATA_DIR, exist_ok=True)

def get_next_sequence(subject_id, camera_id, person_id, repeat_id, action_id):
    pattern = f"S{subject_id:03d}C{camera_id:03d}P{person_id:03d}R{repeat_id:03d}A{action_id:03d}"
    existing_files = [f for f in os.listdir(DATA_DIR) if f.startswith(pattern)]
    
    if not existing_files:
        return pattern
    
    return pattern

@app.route('/api/create-directory', methods=['POST'])
def create_directory():
    data = request.json
    directory_name = data.get('directory_name')
    parent_path = data.get('parent_path', '')
    
    if not directory_name:
        return jsonify({'success': False, 'message': 'Folder name cannot be empty!'})
    
    if parent_path:
        directory_path = os.path.join(DATA_DIR, parent_path, secure_filename(directory_name))
    else:
        directory_path = os.path.join(DATA_DIR, secure_filename(directory_name))
    
    try:
        os.makedirs(directory_path, exist_ok=True)
        return jsonify({
            'success': True,
            'message': f'Successfully created folder {directory_name}',
            'directory_path': directory_path
        })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/get-directories', methods=['GET'])
def get_directories():
    try:
        directories = [d for d in os.listdir(DATA_DIR) 
                      if os.path.isdir(os.path.join(DATA_DIR, d))]
        return jsonify({'success': True, 'directories': directories})
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/save-labels', methods=['POST'])
def save_labels():
    data = request.json
    labels = data.get('labels', [])
    
    try:
        with open(LABELS_FILE, 'w') as f:
            for label in labels:
                f.write(f"{label['name']},{label['status']}\n")
        return jsonify({'success': True, 'message': 'Label list saved!'})
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/get-labels', methods=['GET'])
def get_labels():
    if not os.path.exists(LABELS_FILE):
        return jsonify({'success': True, 'labels': []})
    
    try:
        labels = []
        with open(LABELS_FILE, 'r') as f:
            for line in f:
                if line.strip():
                    parts = line.strip().split(',')
                    if len(parts) >= 2:
                        labels.append({
                            'name': parts[0],
                            'status': parts[1]
                        })
        return jsonify({'success': True, 'labels': labels})
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/save-video', methods=['POST'])
def save_video():
    if 'video' not in request.files:
        return jsonify({'success': False, 'message': 'Videos not found!'})
    
    video_file = request.files['video']
    if not video_file.filename:
        return jsonify({'success': False, 'message': 'Invalid file name'})
    
    subject_id = int(request.form.get('subject_id', 1))
    camera_id = int(request.form.get('camera_id', 1))
    person_id = int(request.form.get('person_id', 1))
    repeat_id = int(request.form.get('repeat_id', 1))
    action_id = int(request.form.get('action_id', 1))
    directory_path = request.form.get('directory_path', '')
    
    filename = f"S{subject_id:03d}C{camera_id:03d}P{person_id:03d}R{repeat_id:03d}A{action_id:03d}.mp4"
    
    save_dir = os.path.join(DATA_DIR, directory_path) if directory_path else DATA_DIR
    os.makedirs(save_dir, exist_ok=True)
    
    file_path = os.path.join(save_dir, filename)
    
    try:
        video_file.save(file_path)
        return jsonify({ 'success': True, 'message': 'Video saved successfully!', 'file_path': file_path, 'filename': filename })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/get-videos', methods=['GET'])
def get_videos():
    directory = request.args.get('directory', '')
    
    videos_dir = os.path.join(DATA_DIR, secure_filename(directory)) if directory else DATA_DIR
    
    if not os.path.exists(videos_dir):
        return jsonify({'success': False, 'message': 'Directory does not exist!'})
    
    try:
        videos = [f for f in os.listdir(videos_dir) 
                  if os.path.isfile(os.path.join(videos_dir, f)) and f.endswith('.mp4')]
        return jsonify({'success': True, 'videos': videos})
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/get-next-ids', methods=['GET'])
def get_next_ids():
    directory_path = request.args.get('directory_path', '')
    action_id = int(request.args.get('action_id', 1))
    
    videos_dir = os.path.join(DATA_DIR, directory_path) if directory_path else DATA_DIR
    
    if not os.path.exists(videos_dir):
        return jsonify({
            'success': True, 
            'suggestion': { 'subject_id': 1, 'camera_id': 1, 'person_id': 1, 'repeat_id': 1, 'action_id': action_id }
        })
    
    try:
        matching_files = [f for f in os.listdir(videos_dir) 
                         if f.endswith('.mp4') and f.startswith(f"S") and f[-8:-4] == f"A{action_id:03d}"]
        
        if not matching_files:
            return jsonify({
                'success': True, 
                'suggestion': { 'subject_id': 1, 'camera_id': 1, 'person_id': 1, 'repeat_id': 1, 'action_id': action_id }
            })
        
        max_subject = max_camera = max_person = max_repeat = 1
        
        for filename in matching_files:
            try:
                s_id = int(filename[1:4])
                c_id = int(filename[5:8])
                p_id = int(filename[9:12])
                r_id = int(filename[13:16])
                
                max_subject = max(max_subject, s_id)
                max_camera = max(max_camera, c_id)
                max_person = max(max_person, p_id)
                max_repeat = max(max_repeat, r_id)
            except:
                continue
        
        if max_repeat < 999:
            max_repeat += 1
        
        return jsonify({
            'success': True, 
            'suggestion': { 'subject_id': max_subject, 'camera_id': max_camera, 'person_id': max_person, 'repeat_id': max_repeat, 'action_id': action_id }
        })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/download-video/<path:filename>', methods=['GET'])
def download_video(filename):
    directory_path = request.args.get('directory_path', '')
    
    videos_dir = os.path.join(DATA_DIR, directory_path) if directory_path else DATA_DIR
    
    try:
        return send_from_directory(videos_dir, filename, as_attachment=True)
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/delete-video', methods=['POST'])
def delete_video():
    data = request.json
    filename = data.get('filename')
    directory_path = data.get('directory_path', '')
    
    if not filename:
        return jsonify({'success': False, 'message': 'Filename not provided!'})
    
    videos_dir = os.path.join(DATA_DIR, directory_path) if directory_path else DATA_DIR
    file_path = os.path.join(videos_dir, filename)
    
    try:
        if os.path.exists(file_path):
            os.remove(file_path)
            return jsonify({
                'success': True,
                'message': f'Video {filename} deleted successfully!'
            })
        else:
            return jsonify({
                'success': False,
                'message': f'File {filename} not found!'
            })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/get-directory-contents', methods=['GET'])
def get_directory_contents():
    path = request.args.get('path', '')
    if path:
        path_segments = path.split('/')
        secured_segments = [secure_filename(segment) for segment in path_segments]
        secured_path = '/'.join(secured_segments)
        full_path = os.path.join(DATA_DIR, secured_path)
    else:
        full_path = DATA_DIR
    
    if not os.path.exists(full_path):
        return jsonify({'success': False, 'message': 'Directory does not exist!'})
    
    try:
        contents = {
            'directories': [],
            'videos': []
        }
        
        for item in os.listdir(full_path):
            item_path = os.path.join(full_path, item)
            if os.path.isdir(item_path):
                contents['directories'].append(item)
            elif os.path.isfile(item_path) and item.endswith('.mp4'):
                contents['videos'].append(item)
                
        return jsonify({'success': True, 'contents': contents, 'current_path': path})
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/extract-pose', methods=['POST'])
def extract_pose():
    data = request.json
    input_folder = data.get('input_folder', '')
    output_folder = data.get('output_folder', '')
    device = data.get('device', 'cuda:0')
    skip_postproc = data.get('skip_postproc', False)
    
    if not input_folder or not output_folder:
        return jsonify({'success': False, 'message': 'Input and output folders are required!'})
    
    if not os.path.exists(os.path.join(DATA_DIR, input_folder)):
        return jsonify({'success': False, 'message': f'Input folder does not exist: {input_folder}'})
    
    os.makedirs(os.path.join(DATA_DIR, output_folder), exist_ok=True)
    process_id = str(time.time())
    
    def run_extraction():
        try:
            full_input_path = os.path.join(DATA_DIR, input_folder)
            full_output_path = os.path.join(DATA_DIR, output_folder)
            extractor = NTUPoseExtractor(device=device, skip_postproc=skip_postproc)
            result = extractor.process_video_folder(full_input_path, full_output_path)
            
            if result:
                running_processes[process_id].update({
                    'status': 'awaiting_confirmation',
                    'current_anno': result['anno'],
                    'annotated_images': result['annotated_images'],
                    'current_video_name': result['video_name'],
                    'remaining_videos': result['remaining_videos'],
                    'total_videos': result['total_videos'],
                    'processed_videos': [],
                    'extractor': extractor,
                    'output_folder': full_output_path
                })
            else:
                running_processes[process_id].update({
                    'status': 'completed',
                    'message': 'No videos to process',
                    'end_time': time.time()
                })
            
        except Exception as e:
            running_processes[process_id] = {
                'status': 'error',
                'message': str(e),
                'input_folder': input_folder,
                'output_folder': output_folder,
                'end_time': time.time()
            }
    
    running_processes[process_id] = {
        'status': 'running',
        'input_folder': input_folder,
        'output_folder': output_folder,
        'progress': 0,
        'start_time': time.time()
    }
    extraction_thread = threading.Thread(target=run_extraction)
    extraction_thread.daemon = True
    extraction_thread.start()
    
    return jsonify({
        'success': True,
        'message': 'Extraction process started',
        'process_id': process_id
    })

@app.route('/api/next-video/<process_id>', methods=['POST'])
def next_video(process_id):
    if process_id not in running_processes:
        return jsonify({'success': False, 'message': 'Process not found'})
    
    process_info = running_processes[process_id]
    
    if 'remaining_videos' not in process_info or not process_info['remaining_videos']:
        process_info['status'] = 'completed'
        process_info['end_time'] = time.time()
        return jsonify({
            'success': True,
            'message': 'All videos processed',
            'status': 'completed',
            'processed_count': len(process_info.get('processed_videos', [])),
            'total_videos': process_info.get('total_videos', 0)
        })
    
    try:
        extractor = process_info['extractor']
        output_folder = process_info['output_folder']
        result = extractor.process_next_video(
            process_info['remaining_videos'],
            output_folder,
            process_info['total_videos']
        )
        
        if result:
            if 'current_video_name' in process_info:
                if 'processed_videos' not in process_info:
                    process_info['processed_videos'] = []
                process_info['processed_videos'].append(process_info['current_video_name'])
            
            process_info.update({
                'status': 'awaiting_confirmation',
                'current_anno': result['anno'],
                'annotated_images': result['annotated_images'],
                'current_video_name': result['video_name'],
                'remaining_videos': result['remaining_videos']
            })
            processed_count = len(process_info.get('processed_videos', []))
            if 'total_videos' in process_info and process_info['total_videos'] > 0:
                process_info['progress'] = int(processed_count / process_info['total_videos'] * 100)
            
            return jsonify({
                'success': True,
                'video_name': result['video_name'],
                'total_frames': len(result['annotated_images']),
                'processed_count': processed_count,
                'total_videos': process_info.get('total_videos', 0)
            })
        else:
            process_info['status'] = 'completed'
            process_info['end_time'] = time.time()
            return jsonify({
                'success': True,
                'message': 'All videos processed',
                'status': 'completed',
                'processed_count': len(process_info.get('processed_videos', [])),
                'total_videos': process_info.get('total_videos', 0)
            })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/extraction-status/<process_id>', methods=['GET'])
def extraction_status(process_id):
    if process_id not in running_processes:
        return jsonify({'success': False, 'message': 'Process not found'})
    
    process_info = running_processes[process_id]
    serializable_info = {
        'status': process_info.get('status', 'unknown'),
        'progress': process_info.get('progress', 0),
        'input_folder': process_info.get('input_folder', ''),
        'output_folder': process_info.get('output_folder', ''),
        'start_time': process_info.get('start_time', 0),
        'end_time': process_info.get('end_time', None),
        'error_details': process_info.get('error_details', None),
        'current_video_index': process_info.get('current_video_index', 0),
        'total_videos': process_info.get('total_videos', 0),
        'processed_count': len(process_info.get('processed_videos', []))
    }
    
    if 'current_video_name' in process_info:
        serializable_info['current_video_name'] = process_info['current_video_name']
    
    if process_info['status'] == 'running':
        try:
            input_folder = os.path.join(DATA_DIR, process_info['input_folder'])
            output_folder = os.path.join(DATA_DIR, process_info['output_folder'])
            
            video_extensions = ('.avi', '.mp4', '.mov', '.mkv')
            video_files = [
                f for f in Path(input_folder).rglob("*") 
                if f.suffix.lower() in video_extensions
            ]
            total_videos = len(video_files)
            
            processed_files = [
                f for f in Path(output_folder).rglob("*.pkl")
            ]
            processed_count = len(processed_files)
            
            if total_videos > 0:
                progress = int((processed_count / total_videos) * 100)
                serializable_info['progress'] = progress
                serializable_info['total_videos'] = total_videos
                serializable_info['processed_count'] = processed_count
                
                running_processes[process_id]['progress'] = progress
        except Exception as e:
            serializable_info['error_details'] = str(e)
    
    return jsonify({
        'success': True,
        'process_info': serializable_info
    })

@app.route('/api/cancel-extraction/<process_id>', methods=['POST'])
def cancel_extraction(process_id):
    if process_id not in running_processes:
        return jsonify({'success': False, 'message': 'Process not found'})
    
    process_info = running_processes[process_id]
    
    try:
       for proc in psutil.process_iter(attrs=['pid', 'name', 'cmdline']):
        if 'ntu_pose_extraction.py' in ' '.join(proc.info['cmdline']):
            proc.terminate()
            time.sleep(2)
            if proc.is_running():
                proc.kill()
        running_processes[process_id]['status'] = 'cancelled'
        running_processes[process_id]['end_time'] = time.time()
        return jsonify({
            'success': True,
            'message': 'Extraction process cancelled'
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error cancelling process: {str(e)}'
        })

@app.route('/api/preview-annotations/<process_id>/<frame_index>', methods=['GET'])
def preview_annotations(process_id, frame_index):
    if process_id not in running_processes:
        return jsonify({'success': False, 'message': 'Process not found'})
    
    process_info = running_processes[process_id]
    if 'annotated_images' not in process_info or not process_info['annotated_images']:
        return jsonify({'success': False, 'message': 'No annotated images available'})
    
    try:
        frame_idx = int(frame_index)
        if frame_idx < 0 or frame_idx >= len(process_info['annotated_images']):
            return jsonify({'success': False, 'message': 'Frame index out of range'})
        
        img = process_info['annotated_images'][frame_idx]
        _, buffer = cv2.imencode('.jpg', img)
        img_str = base64.b64encode(buffer).decode('utf-8')
        
        return jsonify({
            'success': True,
            'image': img_str,
            'total_frames': len(process_info['annotated_images']),
            'current_frame': frame_idx
        })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/confirm-save/<process_id>/<video_name>', methods=['POST'])
def confirm_save(process_id, video_name):
    if process_id not in running_processes:
        return jsonify({'success': False, 'message': 'Process not found'})
    
    process_info = running_processes[process_id]
    if 'current_anno' not in process_info:
        return jsonify({'success': False, 'message': 'No annotation data available'})
    
    data = request.json
    confirm = data.get('confirm', False)
    
    try:
        if confirm:
            output_folder = os.path.join(DATA_DIR, process_info['output_folder'])
            pickle_path = os.path.join(output_folder, f"{video_name}.pkl")
            mmengine.dump(process_info['current_anno'], pickle_path)
            message = f'Annotation saved for {video_name}'
        else:
            message = f'Annotation skipped for {video_name}'
        
        if 'processed_videos' not in process_info:
            process_info['processed_videos'] = []
        process_info['processed_videos'].append(video_name)
        processed_count = len(process_info['processed_videos'])
        
        if 'total_videos' in process_info and process_info['total_videos'] > 0:
            process_info['progress'] = int(processed_count / process_info['total_videos'] * 100)
        
        if 'remaining_videos' not in process_info or not process_info['remaining_videos']:
            process_info['status'] = 'completed'
            process_info['end_time'] = time.time()
        else:
            pass
        
        return jsonify({
            'success': True,
            'message': message,
            'processed_count': processed_count,
            'has_more_videos': bool(process_info.get('remaining_videos', [])),
            'total_videos': process_info.get('total_videos', 0)
        })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error: {str(e)}'})

@app.route('/api/combine-pkl', methods=['POST'])
def combine_pkl():
    data = request.json
    train_folder = data.get('train_folder', '')
    val_folder = data.get('val_folder', '')
    test_folder = data.get('test_folder', '')
    output_file = data.get('output_file', 'ntu_custom_dataset.pkl')
    
    # Ensure at least one input folder is provided
    if not train_folder and not val_folder and not test_folder:
        return jsonify({'success': False, 'message': 'At least one input folder must be specified'})
    
    # Convert relative paths to absolute paths within DATA_DIR
    input_folders = {}
    if train_folder:
        input_folders["train"] = os.path.join(DATA_DIR, train_folder) if not os.path.isabs(train_folder) else train_folder
    if val_folder:
        input_folders["val"] = os.path.join(DATA_DIR, val_folder) if not os.path.isabs(val_folder) else val_folder
    if test_folder:
        input_folders["test"] = os.path.join(DATA_DIR, test_folder) if not os.path.isabs(test_folder) else test_folder
    
    # Handle output file path
    if not os.path.isabs(output_file):
        output_file = os.path.join(DATA_DIR, output_file)
    
    try:
        # Run the combine function
        result = combine_pkl_files(input_folders, output_file)
        return jsonify({
            'success': True,
            'message': result['message'],
            'stats': result['stats'],
            'output_file': result['output_file']
        })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Error combining PKL files: {str(e)}'})

@app.route('/api/list-annotation-files', methods=['GET'])
def list_annotation_files():
    try:
        pkl_files = []
        for root, _, files in os.walk(DATA_DIR):
            for file in files:
                if file.endswith('.pkl'):
                    relative_path = os.path.relpath(os.path.join(root, file), DATA_DIR)
                    pkl_files.append(relative_path)
        
        return jsonify({
            'success': True,
            'files': pkl_files
        })
    except Exception as e:
        return jsonify({
            'success': False,
            'message': f'Error listing annotation files: {str(e)}'
        })

@app.route('/api/train/start', methods=['POST'])
def start_training():
    data = request.json
    
    job_id = str(uuid.uuid4())
    
    try:
        template_config_path = 'configs/skeleton/posec3d/slowonly_r50_8xb16-u48-240e_ntu60-xsub-keypoint.py'
        temp_dir = os.path.dirname(template_config_path)
        os.makedirs(temp_dir, exist_ok=True)
        tmp_config_path = os.path.join(temp_dir, f'temp_config_{job_id}.py')
        
        with open(template_config_path, 'r') as template_file:
            config_content = template_file.read()
            
        config_content = config_content.replace("'num_classes': 4", f"'num_classes': {data.get('num_classes', 4)}")
        config_content = config_content.replace("'dropout_ratio': 0.5", f"'dropout_ratio': {data.get('dropout_ratio', 0.5)}")
        config_content = config_content.replace("ann_file = 'ntu_custom_dataset.pkl'", f"ann_file = '{data.get('ann_file', 'ntu_custom_dataset.pkl')}'")
        config_content = config_content.replace("clip_len=50", f"clip_len={data.get('clip_len', 50)}")
        config_content = config_content.replace("batch_size=8", f"batch_size={data.get('batch_size', 8)}")
        config_content = config_content.replace("num_workers=4", f"num_workers={data.get('num_workers', 4)}")
        config_content = config_content.replace("max_epochs=24", f"max_epochs={data.get('max_epochs', 24)}")
        config_content = config_content.replace("lr=0.001", f"lr={data.get('learning_rate', 0.001)}")
        config_content = config_content.replace("weight_decay=0.0003", f"weight_decay={data.get('weight_decay', 0.0003)}")
        
        if data.get('scheduler') == 'StepLR':
            scheduler_section = "param_scheduler = [\n    dict(type='StepLR', step_size=8, gamma=0.1, by_epoch=True, convert_to_iter_based=True)\n]"
            config_content = config_content.replace("param_scheduler = [\n    dict(type='CosineAnnealingLR', eta_min=0, T_max=24, by_epoch=True, convert_to_iter_based=True)\n]", scheduler_section)
        
        with open(tmp_config_path, 'w') as tmp_config:
            tmp_config.write(config_content)
        
        work_dir = data.get('work_dir', 'train_results/slowonly_r50')
        cmd = [
            'python', 'tools/train.py', 
            tmp_config_path, 
            '--work-dir', work_dir,
            '--seed', str(data.get('seed', 0))
        ]
        
        if data.get('use_amp'):
            cmd.append('--amp')
        
        log_dir = os.path.join(work_dir, 'logs')
        os.makedirs(log_dir, exist_ok=True)
        log_file = os.path.join(log_dir, f'train_{job_id}.log')
        
        with open(log_file, 'w') as f:
            process = subprocess.Popen(
                cmd,
                stdout=f,
                stderr=subprocess.STDOUT,
                text=True,
                bufsize=1,
                universal_newlines=True
            )
        
        training_jobs[job_id] = {
            'process': process,
            'pid': process.pid,
            'status': 'running',
            'start_time': time.time(),
            'config': data,
            'log_file': log_file,
            'work_dir': work_dir,
            'last_log_position': 0,
            'progress': 0,
            'temp_config': tmp_config_path
        }
        
        return jsonify({
            'success': True, 
            'message': 'Training started successfully', 
            'job_id': job_id,
            'config_path': tmp_config_path
        })
    
    except Exception as e:
        return jsonify({'success': False, 'message': f'Failed to start training: {str(e)}'})

@app.route('/api/train/status/<job_id>', methods=['GET'])
def get_training_status(job_id):
    if job_id not in training_jobs:
        return jsonify({'success': False, 'message': 'Training job not found'})
    
    job = training_jobs[job_id]
    
    if job['process'].poll() is not None:
        exit_code = job['process'].returncode
        if exit_code == 0:
            job['status'] = 'completed'
            if 'temp_config' in job and os.path.exists(job['temp_config']):
                try:
                    os.remove(job['temp_config'])
                    print(f"Temp config file {job['temp_config']} removed after successful completion")
                except Exception as e:
                    print(f"Failed to remove temp config file after completion: {e}")
        else:
            job['status'] = 'failed'
            job['error'] = f'Process exited with code {exit_code}'
            if 'temp_config' in job and os.path.exists(job['temp_config']):
                try:
                    os.remove(job['temp_config'])
                    print(f"Temp config file {job['temp_config']} removed after job failure")
                except Exception as e:
                    print(f"Failed to remove temp config file after failure: {e}")
    
    new_logs = []
    try:
        with open(job['log_file'], 'r') as f:
            f.seek(job['last_log_position'])
            new_logs = [line.rstrip() for line in f]
            job['last_log_position'] = f.tell()
    except Exception as e:
        print(f"Error reading log file: {e}")
    
    try:
        if job['status'] == 'running':
            max_epochs = int(job['config'].get('max_epochs', 24))
            current_epoch = 0
            
            with open(job['log_file'], 'r') as f:
                for line in f:
                    if 'Epoch(train)' in line:
                        match = re.search(r'Epoch\(train\)\s*\[(\d+)\]', line)
                        if match:
                            epoch = int(match.group(1))
                            if epoch > current_epoch:
                                current_epoch = epoch
            
            if current_epoch > 0:
                job['progress'] = (current_epoch / max_epochs) * 100
                print(f"Progress updated: {current_epoch}/{max_epochs} = {job['progress']}%")
    except Exception as e:
        print(f"Error updating progress: {e}")
    
    response = {
        'success': True,
        'status': job['status'],
        'logs': new_logs,
        'progress': job['progress']
    }
    
    if job['status'] == 'failed' and 'error' in job:
        response['error'] = job['error']
    
    return jsonify(response)

@app.route('/api/train/stop/<job_id>', methods=['POST'])
def stop_training(job_id):
    if job_id not in training_jobs:
        return jsonify({'success': False, 'message': 'Training job not found'})
    
    job = training_jobs[job_id]
    
    try:
        if job['process'].poll() is None:
            job['process'].terminate()
            time.sleep(2)
            if job['process'].poll() is None:
                job['process'].kill()
        
        job['status'] = 'stopped'
        job['end_time'] = time.time()
        
        if 'temp_config' in job and os.path.exists(job['temp_config']):
            try:
                os.remove(job['temp_config'])
            except Exception as e:
                print(f"Failed to remove temp config file: {e}")
        
        return jsonify({
            'success': True,
            'message': 'Training job stopped successfully'
        })
    except Exception as e:
        return jsonify({'success': False, 'message': f'Failed to stop training: {str(e)}'})

if __name__ == '__main__':
    app.run(debug=True, use_reloader=True)