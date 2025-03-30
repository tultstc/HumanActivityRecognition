<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class StreamingController extends Controller
{
    public function index()
    {
        $cameras = Cache::remember('streaming', Carbon::now()->addMinutes(30), function () {
            return Camera::select('ten', 'id')->get();
        });

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
        $cameras = Camera::findOrFail($id)->select('ten', 'id');

        return $cameras[$id] ?? null;
    }
}
