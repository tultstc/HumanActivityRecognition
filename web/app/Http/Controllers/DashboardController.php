<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Camera;
use App\Models\Layout;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Redis;

class DashboardController extends Controller
{
    public function index()
    {
        $all_cameras = Camera::orderBy('name')->get();
        $cameras = Camera::where('status', '=', 1)->orderBy('name')->get();
        $events = Notification::with('camera')->orderBy('start_error_time', 'desc')->limit(3)->get();
        $eventsWithPage = $events->map(function ($event) {
            $position = Notification::where('start_error_time', '>=', $event->start_error_time)
                ->orderBy('start_error_time', 'desc')
                ->count();

            return [
                'id' => $event->id,
                'page' => ceil($position / 10)
            ];
        });
        $active = 0;
        $inactive = 0;
        $preferences = Layout::firstOrCreate(
            ['user_id' => Auth::user()->id],
            [
                'grid_rows' => 3,
                'grid_columns' => 4,
                'selected_camera_ids' => []
            ]
        );
        foreach ($all_cameras as $camera) {
            $key = "camera_{$camera->id}_status";
            $camera_info = "camera_{$camera->id}_info";

            $status = Redis::get($key);
            $info = Redis::get($camera_info);

            if ($status === 'True' && $info !== null) {
                $active++;
            } else {
                $inactive++;
            }
        }

        $total = $all_cameras->count();

        $stats = [
            'total' => $total,
            'active' => $active,
            'offline' => $inactive,
        ];

        return view('dashboard', [
            'stats' => $stats,
            'cameras' => $cameras,
            'events' => $events,
            'eventsWithPage' => $eventsWithPage,
            'userPreferences' => $preferences
        ]);
    }

    public function getCameras(Request $request)
    {
        $locationId = $request->input('locationId');

        $cameras = Camera::published()
            ->with(['group', 'position', 'cameraModels'])
            ->when($locationId, function ($query) use ($locationId) {
                $query->whereHas('position', function ($q) use ($locationId) {
                    $q->where('khuvucid', $locationId);
                });
            })
            ->get();

        return response()->json($cameras);
    }

    public function savePreferences(Request $request)
    {
        $validated = $request->validate([
            'grid_rows' => 'required|integer|min:1',
            'grid_columns' => 'required|integer|min:1',
            'selected_camera_ids' => 'nullable|array'
        ]);

        $preferences = Layout::updateOrCreate(
            ['user_id' => Auth::user()->id],
            [
                'grid_rows' => $validated['grid_rows'],
                'grid_columns' => $validated['grid_columns'],
                'selected_camera_ids' => $validated['selected_camera_ids'] ?? []
            ]
        );

        return response()->json([
            'message' => 'Preferences saved successfully',
            'preferences' => $preferences
        ]);
    }

    private function getLocationGroups()
    {
        return Cache::remember('location_groups', now()->addMinutes(30), function () {
            return Area::with(['positions' => function ($query) {
                $query->with(['cameras' => function ($q) {
                    $q->select('id', 'ten', 'vitriid');
                }])->withCount('cameras');
            }])->get()->map(function ($area) {
                return [
                    'label' => $area->ten,
                    'id' => $area->id,
                    'positions' => $area->positions->map(function ($position) {
                        return [
                            'label' => $position->ten,
                            'id' => $position->id,
                            'camera_count' => $position->cameras_count,
                            'cameras' => $position->cameras->map(function ($camera) {
                                return [
                                    'id' => $camera->id,
                                    'label' => $camera->ten
                                ];
                            })
                        ];
                    })
                ];
            });
        });
    }

    public function stream(Request $request, $cameraId)
    {
        $camera = $this->getCameraById($cameraId);

        if (!$camera) {
            return response()->json(['error' => 'Camera not found'], 404);
        }

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