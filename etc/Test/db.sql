INSERT INTO "cameras" ("id", "name", "stream_url", "status", "config", "model_id", "created_at", "updated_at") VALUES
	(1, 'Camera 1', 'rtsp://admin:Admin123456*@@192.168.8.191:554/Streaming/channels/101', 1, '{"fps":5,"maxoutframe":10,"mask":[]}', 1, '2024-11-16 16:28:28', '2024-12-03 18:41:27'),
	(6, 'Camera 6', 'rtsp://admin:abcd1234@192.168.0.214:554/cam/realmonitor?channel=2%26subtype=0', 1, '{"fps":5,"mask":[]}', 1, '2024-11-16 16:57:40', '2024-12-03 18:54:05'),
	(8, 'Camera 8', 'rtsp://admin:abcd1234@192.168.0.214:554/cam/realmonitor?channel=4%26subtype=0', 1, '{"fps":5}', 1, '2024-11-16 16:57:40', '2024-11-16 16:57:40'),
	(11, 'Camera 11', 'rtsp://cam184:Stc@2024@192.168.8.184:554/profile1', 1, '{"fps":5,"maxoutframe":10,"mask":{"polygon1":[[636,288],[602,1054],[1368,1042],[1382,72]]}}', 1, '2024-11-28 15:34:39', '2024-11-28 15:34:39'),
	(10, 'Camera 10', 'rtsp://admin:abcd1234@192.168.0.214:554/cam/realmonitor?channel=8%26subtype=0', 1, '{"fps":5}', 1, '2024-11-16 16:57:40', '2024-11-16 16:57:40'),
	(9, 'Camera 9', 'rtsp://admin:abcd1234@192.168.0.214:554/cam/realmonitor?channel=7%26subtype=0', 1, '{"fps":5}', 1, '2024-11-16 16:57:40', '2024-11-16 16:57:40'),
	(5, 'Camera 5', 'rtsp://admin:abcd1234@192.168.0.214:554/cam/realmonitor?channel=1%26subtype=0', 1, '{"fps":5}', 1, '2024-11-16 16:56:49', '2024-11-16 16:56:49'),
	(3, 'Camera 3', 'rtsp://admin:Stc%40vielina.com@192.168.8.192:554/Streaming/channels/101', 1, '{"fps":5,"maxoutframe":10,"mask":{"polygon1":[[802,408],[806,830],[1090,814],[1090,398]]}}', 1, '2024-11-16 16:30:33', '2024-12-03 18:38:41'),
	(7, 'Camera 7', 'rtsp://admin:abcd1234@192.168.0.214:554/cam/realmonitor?channel=3%26subtype=0', 1, '{"fps":5}', 1, '2024-11-16 16:57:40', '2024-11-16 16:57:40'),
	(12, 'Camera 12', 'rtsp://cam188:Stc@2024@192.168.8.188:554/profile1', 1, '{"fps":5,"maxoutframe":10,"mask":[]}', 1, '2024-11-28 17:30:24', '2024-12-03 18:41:50'),
	(14, 'Camera 14', 'rtsp://cam187:Stc@2024@192.168.8.187:554/profile1', 1, '{"fps":5,"maxoutframe":10,"mask":{"polygon1":[[636,288],[602,1054],[1368,1042],[1382,72]]}}', 1, '2024-11-28 17:30:24', '2024-11-28 17:30:24'),
	(15, 'Camera 15', 'rtsp://cam185:Stc@2024@192.168.8.185:554/profile1', 1, '{"fps":5,"maxoutframe":10,"maxinframe":10,"mask":[]}', 1, '2024-11-28 17:30:24', '2024-12-03 18:49:06'),
	(2, 'Camera 2', 'rtsp://admin:Admin123456*@@192.168.8.193:554/Streaming/channels/101', 1, '{"fps":5,"maxoutframe":10,"mininframe":10,"mask":[]}', 1, '2024-11-16 16:28:28', '2024-12-04 10:53:13'),
	(4, 'Camera 4', 'rtsp://admin:GSSFXW@192.168.8.2:554/ch1/main', 1, '{"fps":5}', 1, '2024-11-16 16:31:24', '2024-12-04 13:35:22'),
	(13, 'Camera 13', 'rtsp://cam186:Stc@2024@192.168.8.186:554/profile1', 1, '{"fps":5,"maxoutframe":10,"mask":{"polygon1":[[636,288],[602,1054],[1368,1042],[1382,72]]}}', 1, '2024-11-28 17:30:24', '2024-11-28 17:30:24');

INSERT INTO "models" ("id", "name", "url", "config", "status", "created_at", "updated_at") VALUES
	(1, 'Model Default', 'model/yolo11n.pt', '{"conf":0.5,"label_conf":[0]}', 1, NULL, NULL),
	(2, 'Model Pose', 'model/yolo11n-pose.pt', '{"conf":0.5,"label_conf":[0],"annotators":{"box_annotator":{"type":"BoxAnnotator","thickness":2},"label_annotator":{"type":"LabelAnnotator","text_position":"TOP_CENTER","text_thickness":2,"text_scale":1}}}', 1, NULL, NULL),
	(3, 'Model Count', 'model/yolo11n.pt', '{"conf":0.5,"label_conf":[0],"annotators":{"box_annotator":{"type":"BoxAnnotator","thickness":2},"label_annotator":{"type":"LabelAnnotator","text_position":"TOP_CENTER","text_thickness":2,"text_scale":1}}}', 1, NULL, NULL),
	(4, 'Model Tracking', 'model/yolo11n.pt', '{"conf":0.5,"label_conf":[0],"annotators":{"box_annotator":{"type":"BoxAnnotator","thickness":2},"label_annotator":{"type":"LabelAnnotator","text_position":"TOP_CENTER","text_thickness":2,"text_scale":1}}}', 1, NULL, NULL);