<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RTSPController extends Controller
{
    public function index()
    {
        // Lấy danh sách camera từ cơ sở dữ liệu hoặc cấu hình
        $cameras = [
            ['id' => 1, 'name' => 'Camera 1', 'url' => 'rtsp://admin:Admin123456*@@192.168.8.191:554/Streaming/channels/101'],
            ['id' => 2, 'name' => 'Camera 2', 'url' => 'rtsp://admin:Admin123456*@@192.168.8.193:554/Streaming/channels/101'],
            ['id' => 3, 'name' => 'Camera 3', 'url' => 'rtsp://admin:Stc@vielina.com@192.168.8.192:554/cam/realmonitor?channel=1&subtype=0'],
        ];

        return view('dashboard', compact('cameras'));
    }

    public function stream(Request $request, $cameraId)
    {
        // Lấy thông tin camera từ cơ sở dữ liệu hoặc cấu hình
        $camera = $this->getCameraById($cameraId);

        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

        // Chuyển tiếp yêu cầu đến Node.js server xử lý RTSP
        $nodeServerUrl = env('NODE_SERVER_URL', 'http://localhost:3000');
        $response = Http::get("{$nodeServerUrl}/api/stream/{$cameraId}");

        return $response->body();
    }

    private function getCameraById($id)
    {
        // Giả lập lấy thông tin camera, trong thực tế bạn sẽ truy vấn từ cơ sở dữ liệu
        $cameras = [
            1 => ['id' => 1, 'name' => 'Camera 1', 'url' => 'rtsp://admin:Admin123456*@@192.168.8.191:554/Streaming/channels/101'],
            2 => ['id' => 2, 'name' => 'Camera 2', 'url' => 'rtsp://admin:Admin123456*@@192.168.8.193:554/Streaming/channels/101'],
            3 => ['id' => 3, 'name' => 'Camera 3', 'url' => 'rtsp://admin:Stc@vielina.com@192.168.8.192:554/cam/realmonitor?channel=1&subtype=0'],
        ];

        return $cameras[$id] ?? null;
    }
}