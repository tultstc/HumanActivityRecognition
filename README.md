# STC-VideoAnalysisHAR

STC-VideoAnalysisHAR (fork from [VisionFlow](https://github.com/dan246/VisionFlow) and combined with mmaction [MMAction](https://github.com/open-mmlab/mmaction2.git)) is a backend application designed for human action recognition systems. This project utilizes the Flask framework and leverages PostgreSQL for data management. Redis is integrated to handle the processing and distribution of camera images. All environments can be configured and managed using Docker.

## Table of Contents

- [Project Introduction](#project-introduction)
- [Quick Start](#quick-start)
  - [Prerequisites](#prerequisites)
  - [Setup Steps](#setup-steps)
- [Redis Features](#redis-features)
  - [Image Processing](#image-processing)
  - [Image Recognition and Annotation](#image-recognition-and-annotation)
  - [Camera Allocation](#camera-allocation)
- [API Documentation](#api-documentation)
  - [User Authentication API](#user-authentication-api)
  - [Camera Management API](#camera-management-api)
  - [Notification Management API](#notification-management-api)
  - [LINE Token Management API](#line-token-management-api)
  - [Email Recipient Management API](#email-recipient-management-api)
  - [Image Processing and Streaming API](#image-processing-and-streaming-api)
- [Usage Examples](#usage-examples)
- [Notes](#notes)
- [License](#license)
- [Contributions & Contact](#contributions--contact)
- [References](#references)

## Project Introduction

STC-VideoAnalysisHAR is a backend application aimed at human action recognition. The application provides functionalities for user authentication, camera management, collect data, label and training.

## Quick Start

### Prerequisites

Before you begin, ensure you have the following tools installed:

- [Docker](https://www.docker.com/)
- [Docker Compose](https://docs.docker.com/compose/)

### Setup Steps

<!-- 0. **License setup for Hardlock key**

   ```bash
   - Install usbipd-win_4.3.0.msi in etc folder
   - Install WSL-USB-5.5.0.msi in etc folder
   - Open WSL USB, add Harkey ID to AutoLoad WSL.The name similar is OEM USB DONGLE
   ``` -->

1. **Clone the Project to Your Local Environment:**

   ```bash
   git clone https://github.com/stcrepo/VideoAnalysisHAR.git
   cd VideoAnalysisHAR
   sudo apt-get update && sudo apt-get upgrate
   sudo apt install unzip
   cd etc/posec3d/slowonly_r50_8xb16-u48-240e_ntu60-xsub-keypoint
   unzip epoch_24.zip

   - download checkpoints
   https://download.openmmlab.com/mmdetection/v2.0/faster_rcnn/faster_rcnn_r50_fpn_1x_coco-person/faster_rcnn_r50_fpn_1x_coco-person_20201216_175929-d022e227.pth

   https://download.openmmlab.com/mmpose/top_down/hrnet/hrnet_w32_coco_256x192-c78dce93_20200708.pth

   http://download.openmmlab.com/mmdetection/v2.0/faster_rcnn/faster_rcnn_r50_fpn_2x_coco/faster_rcnn_r50_fpn_2x_coco_bbox_mAP-0.384_20200504_210434-a5d8aa15.pth

   https://download.openmmlab.com/mmpose/v1/projects/rtmposev1/rtmpose-m_simcc-body7_pt-body7_420e-256x192-e48f03d0_20230504.pth

   https://download.openmmlab.com/mmpose/v1/projects/rtmposev1/rtmdet_m_8xb32-100e_coco-obj365-person-235e8209.pth

   - copy to etc/pose/demo
   ```

2. **Start the Services Using Docker Compose:**

   ```bash
   (Make sure to configure the .env file in cameraweb service before building image)
   docker compose -f docker-compose-dev.yaml up -d --build
   ```

   This command will automatically download the required Docker images, install the necessary packages, and start the Flask application on `http://localhost:5000`.

3. **Configure Redis and Multiple Worker Nodes:**

   If you need to set up Redis with multiple worker nodes, change value on docker-compose.yaml:

   ```bash
   docker-compose -f docker-compose.yaml up -d
   ```

   This will start the Redis service along with multiple worker nodes to handle image recognition and camera allocation tasks.

4. **Replace Models in `camera_analysis`:**

   Replace the models with your own model URLs, ensuring the files are named `best.pt`. You can set multiple model URLs without worrying about `.pt` models being overwritten.

## Redis Features

### Image Processing

STC-VideoAnalysisHAR uses Redis to manage camera image data. Camera images are stored in Redis and distributed to different worker nodes for processing. Each image, after recognition, is stored as a separate Redis key for subsequent use.
NOTE: Number of worker change from docker-compose.yaml and (docker-compose-redis.yaml or docker-compose-redis-gpu.yaml)

1. **Image Storage and Retrieval:**

   - The latest image from each camera is stored in Redis using the key `camera_{camera_id}_latest_frame`.
   - Access the processed image results via `camera_{camera_id}_boxed_image`.

2. **Image Recognition Workflow:**
   - When a camera captures an image, it is stored in Redis with the key `camera_{camera_id}_latest_frame`.
   - Workers extract the image from Redis for recognition processing and store the processed image result in `camera_{camera_id}_boxed_image`.

### Camera Allocation

To efficiently manage image processing from multiple cameras, STC-VideoAnalysisHAR configures multiple worker nodes. These nodes distribute the processing workload, enhancing system efficiency. Each worker extracts camera images from Redis for processing, ensuring system stability and scalability.

## API Documentation

### Camera Management API

- **Get All Cameras List**

  ```http
  GET /cameras
  ```

  **Response:**

  ```json
  [
    {
      "id": 1,
      "name": "Camera 1",
      "stream_url": "http://camera-stream-url",
      "location": "Entrance"
    },
    {
      "id": 2,
      "name": "Camera 2",
      "stream_url": "http://camera-stream-url",
      "location": "Lobby"
    }
  ]
  ```

### Image Processing and Streaming API

- **Get Camera Status**

  ```http
  GET /camera_status
  ```

  **Response:**

  ```json
  {
      "camera_1": "active",
      "camera_2": "inactive",
      ...
  }
  ```

- **Get Latest Snapshot from a Camera**

  ```http
  GET /get_snapshot/<camera_id>
  ```

  **Response:**

  Returns a JPEG image data stream directly.

- **Retrieve Image from a Specific Path**

  ```http
  GET /image/<path:image_path>
  ```

  **Response:**

  Returns the image file from the specified path.

- **Get Live Image Stream**

  ```http
  GET /get_stream/<int:ID>
  ```

  **Response:**

  Returns a live image stream from the specified camera.

- **Get Live Image Stream with Recognition**

  ```http
  GET /recognized_stream/<ID>
  ```

  **Response:**

  Returns a live image stream that has been processed for recognition.

- **Display Camera Snapshot and Rectangle Areas**

  ```http
  GET /snapshot_ui/<ID>
  ```

  **Response:**

  Displays the camera snapshot along with drawn rectangle areas (focus areas) using an HTML template. After setting, the model will only focus on the areas within the rectangles.

- **Manage Rectangle Areas**

  ```http
  POST /rectangles/<ID>
  GET /rectangles/<ID>
  DELETE /rectangles/<ID>
  ```

  **Request Body (POST):**

  ```json
  [
      {
          "x": 100,
          "y": 200,
          "width": 50,
          "height": 60
      },
      ...
  ]
  ```

  **Response:**

  - **POST:** `Rectangles saved`
  - **GET:** Returns all rectangle areas for the specified camera.
  - **DELETE:** `All rectangles cleared`

## Usage Examples

Here are some examples of how to use the STC-VideoAnalysisHAR API:

1. **Register a New User and Login**

   ```bash
   curl -X POST http://localhost:5000/register -H "Content-Type: application/json" -d '{"username": "user1", "email": "user1@example.com", "password": "password123"}'
   curl -X POST http://localhost:5000/login -H "Content-Type: application/json" -d '{"username": "user1", "password": "password123"}'
   ```

2. **Add a New Camera**

   ```bash
   curl -X POST http://localhost:5000/cameras -H "Content-Type: application/json" -d '{"name": "Camera 1", "stream_url": "http://camera-stream-url", "location": "Entrance"}'
   ```

3. **Create a New Notification**

   ```bash
   curl -X POST http://localhost:5000/notifications -H "Content-Type: application/json" -d '{"account_uuid": "your-account-uuid", "camera_id": 1, "message": "Detected event", "image_path": "/path/to/image.jpg"}'
   ```

4. **Get Camera Status**

   ```bash
   curl -X GET http://localhost:15440/camera_status
   ```

5. **Get Live Image Stream from a Camera**

   ```bash
   curl -X GET http://localhost:15440/get_stream/1
   ```

6. **Get Live Image Stream with Recognition**

   ```bash
   curl -X GET http://localhost:15440/recognized_stream/1
   ```

## Notes

1. **Environment Variables:** If needed, ensure that `DATABASE_URL`, `SECRET_KEY`, and `REDIS_URL` are correctly set in the `.env` file. Defaults are provided in the code, so you can skip this step if necessary.

2. **Database Migration:** To update the database or add new tables, after modifying `web/models/`, execute `flask db migrate` and `flask db upgrade` to update the database schema.

3. **Redis Configuration:** Use Redis to manage image data storage and processing. Ensure Redis is running properly and connected to worker nodes.

4. **Docker Startup:** Use Docker Compose to manage the application’s startup and shutdown, especially when needing to start multiple worker nodes.

5. **Data Backup:** Regularly back up your PostgreSQL database and Redis data to prevent data loss.

6. **Model Paths:** Replace models with your own models (located in `object_recognition/app.py`).

## License

This project is licensed under the [MIT License](LICENSE).

## Contributions & Contact

If you have any questions or would like to contribute to this project, please feel free to contact me. Your feedback is highly valuable and will help improve the project. You can open an issue or submit a pull request on GitHub. Alternatively, you can reach me directly through the contact details provided below.

### Contact & Contributions

If you have any questions or would like to contribute to this project, please feel free to contact me. Your feedback is highly valuable and will help improve the project. You can open an issue or submit a pull request on GitHub. Alternatively, you can reach me directly through the contact details provided below.

stc@vielina.com

## References

- [Supervision by Roboflow](https://github.com/roboflow/supervision)
- [Install Python 3.10 Ubuntu](https://gist.github.com/rutcreate/c0041e842f858ceb455b748809763ddb)
