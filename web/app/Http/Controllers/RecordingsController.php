<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RecordingsController extends Controller
{
    protected $cameraControlUrl;

    public function __construct()
    {
        $this->cameraControlUrl = env('CAMERA_CONTROL_URL', 'http://cameracontrol:5000');
    }

    public function index()
    {
        return view('recordings.index');
    }

    public function list()
    {
        try {
            $response = Http::get("{$this->cameraControlUrl}/recordings/list");

            if ($response->successful()) {
                return $response->json();
            }

            return response()->json(['error' => 'Failed to retrieve recordings'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function preview($path)
    {
        try {
            $response = Http::get("{$this->cameraControlUrl}/recordings/preview/{$path}");

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', 'image/jpeg');
            }

            return response()->json(['error' => 'Failed to retrieve preview'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function stream(Request $request)
    {
        $path = $request->input('path');
        $fps = $request->input('fps', 15);

        if (!$path) {
            return response()->json(['error' => 'No path provided'], 400);
        }

        $url = "{$this->cameraControlUrl}/recordings/stream?path={$path}&fps={$fps}";

        return response()->stream(function () use ($url) {
            $client = new \GuzzleHttp\Client();
            $response = $client->request('GET', $url, [
                'stream' => true
            ]);

            $body = $response->getBody();
            while (!$body->eof()) {
                echo $body->read(1024);
                flush();
            }
        }, 200, [
            'Content-Type' => 'multipart/x-mixed-replace; boundary=frame',
            'Cache-Control' => 'no-cache, private',
        ]);
    }

    public function metadata(Request $request)
    {
        $path = $request->input('path');

        if (!$path) {
            return response()->json(['error' => 'No path provided'], 400);
        }

        try {
            $response = Http::get("{$this->cameraControlUrl}/recordings/metadata", [
                'path' => $path
            ]);

            if ($response->successful()) {
                return $response->json();
            }

            return response()->json(['error' => 'Failed to retrieve metadata'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function frame(Request $request)
    {
        $path = $request->input('path');
        $frame = $request->input('frame', 0);

        if (!$path) {
            return response()->json(['error' => 'No path provided'], 400);
        }

        try {
            $response = Http::get("{$this->cameraControlUrl}/recordings/frame", [
                'path' => $path,
                'frame' => $frame
            ]);

            if ($response->successful()) {
                return response($response->body(), 200)
                    ->header('Content-Type', 'image/jpeg');
            }

            return response()->json(['error' => 'Failed to retrieve frame'], 500);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}