<?php

namespace App\Http\Controllers;

use App\Models\AiModel;
use App\Models\Area;
use App\Models\Camera;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Redis;

class CameraController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('view camera'), only: ['index']),
            new Middleware(PermissionMiddleware::using('create camera'), only: ['create', 'store']),
            new Middleware(PermissionMiddleware::using('update camera'), only: ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('delete camera'), only: ['destroy']),
        ];
    }

    public function index()
    {
        $cameras = Camera::get();
        return view('cameras.index', compact('cameras'));
    }

    public function getAll()
    {
        $cameras = Camera::with(['model:id,name,url'])->active()->get();
        return response()->json($cameras, 200);
    }

    public function getByid($id)
    {
        $camera = Camera::findOrFail($id);
        return response()->json($camera, 200);
    }

    public function create()
    {
        $models = AiModel::all();

        return view('cameras.create', ['models' => $models]);
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'id' => 'required|unique:cameras,id',
            'name' => 'required|string|max:255',
            'model_id' => 'nullable|exists:models,id',
            'stream_url' => 'required|string|max:255',
            'status' => 'required|in:0,1',
            'config' => 'required|string',
        ]);

        $configJson = $validatedData['config'];
        $decodedConfig = json_decode($configJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()
                ->back()
                ->withErrors(['config' => 'Config must be json.'])
                ->withInput();
        }

        Camera::create([
            'id' => $validatedData['id'],
            'name' => $validatedData['name'],
            'model_id' => $validatedData['model_id'],
            'stream_url' => $validatedData['stream_url'],
            'status' => $validatedData['status'],
            'config' => json_encode($decodedConfig),
        ]);

        return redirect(route('cameras'))->with('status', 'Successfully created Camera!');
    }

    public function edit($cameraId)
    {
        $camera = Camera::with('model')->findOrFail($cameraId);
        $models = AiModel::all();
        return view('cameras.edit', ['camera' => $camera, 'models' => $models]);
    }

    public function update(Request $request, $cameraId)
    {
        $camera = Camera::findOrFail($cameraId);

        $validatedData = $request->validate([
            'id' => $cameraId == $request->id ? 'integer' : 'unique:cameras,id',
            'name' => 'string|max:255',
            'model_id' => 'nullable|exists:models,id',
            'stream_url' => 'string|max:255',
            'status' => 'in:0,1,2,3',
            'config' => 'string|required',
            'mask' => 'nullable|string',
        ]);

        $configJson = $validatedData['config'];
        $decodedConfig = json_decode($configJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return redirect()
                ->back()
                ->withErrors(['config' => 'Config must be a valid JSON.'])
                ->withInput();
        }

        if (!empty($validatedData['mask'])) {
            $maskString = $validatedData['mask'];

            preg_match_all('/\[(.*?)\]/', $maskString, $matches);

            $formattedMask = [];
            foreach ($matches[1] as $index => $polygon) {
                $points = explode('), (', $polygon);
                $formattedMask["polygon" . ($index + 1)] = array_map(function ($point) {
                    $coords = explode(',', str_replace(['(', ')'], '', $point));
                    return array_map('intval', $coords);
                }, $points);
            }

            $decodedConfig['mask'] = $formattedMask;
        }

        $validatedData['config'] = json_encode($decodedConfig);

        $cameraData = [
            'name' => $validatedData['name'],
            'model_id' => $validatedData['model_id'],
            'stream_url' => $validatedData['stream_url'],
            'status' => $validatedData['status'],
            'config' => $validatedData['config'],
        ];

        if ($cameraId !== $request->id) {
            $cameraData['id'] = $validatedData['id'];
        }

        $camera->update($cameraData);
        $validatedData['status'] == 1 ? Redis::set(`camera_{$cameraId}_status`, 'False') : Redis::set(`camera_{$cameraId}_status`, 'False');

        return redirect(route('cameras'))->with('status', 'Successfully updated Camera!');
    }


    public function detail()
    {
        $areas =  Area::with(['positions'])->get();

        $cameras = Cache::remember('cameras_details', Carbon::now()->addMinutes(30), function () {
            return Camera::select('ten', 'id')->get();
        });

        return view('cameras.detail', ['areas' => $areas]);
    }

    public function destroy($cameraId)
    {
        try {
            $camera = Camera::findOrFail($cameraId);
            $camera->delete();

            return response()->json([
                'success' => true,
                'message' => 'Camera deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the camera. ' . $e->getMessage()
            ], 500);
        }
    }

    public function getRtspUrl($id)
    {
        $url = Cache::remember("camera_url_{$id}", Carbon::now()->addMinutes(30), function () use ($id) {
            $camera = Camera::find($id);
            return $camera ? $camera->getRtspUrl() : null;
        });

        if (!$url) {
            return response()->json([
                'error' => 'Camera not found or inactive'
            ], 404);
        }

        return response()->json([
            'rtspUrl' => $url
        ]);
    }
}