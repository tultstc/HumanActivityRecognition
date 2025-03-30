<?php

namespace App\Http\Controllers;

use App\Events\CameraEventUpdated;
use App\Models\Notification;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Middleware\PermissionMiddleware;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('view event'), only: ['index']),
            new Middleware(PermissionMiddleware::using('delete event'), only: ['destroy']),
        ];
    }

    public function index()
    {
        $events =  Notification::get();
        return view('events.index', ['events' => $events]);
    }

    public function getEvents(Request $request)
    {
        $query = Notification::query()->with('camera');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('start_error_time', [
                Carbon::createFromFormat('m/d/Y', $request->start_date)->startOfDay(),
                Carbon::createFromFormat('m/d/Y', $request->end_date)->endOfDay()
            ]);
        }

        $totalData = $query->count();

        if ($request->search['value']) {
            $searchValue = $request->search['value'];
            $query->where(function ($q) use ($searchValue) {
                $q->where('id', 'like', "%{$searchValue}%")
                    ->orWhere('start_error_time', 'like', "%{$searchValue}%")
                    ->orWhere('end_error_time', 'like', "%{$searchValue}%");
            });
        }

        $totalFiltered = $query->count();

        if (isset($request->order[0])) {
            $orderColumnIndex = $request->order[0]['column'];
            $orderColumn = $request->columns[$orderColumnIndex]['data'] ?? 'id';
            $orderDir = $request->order[0]['dir'] ?? 'asc';
            $query->orderBy($orderColumn, $orderDir);
        } else {
            $query->orderBy('id', 'asc');
        }

        $query->skip($request->start)->take($request->length);

        $events = $query->get();

        $data = [];
        foreach ($events as $event) {
            $nestedData = [
                'id' => $event->id,
                'start_error_time' => $event->start_error_time,
                'end_error_time' => $event->end_error_time,
            ];

            $deleteUrl = url('events/' . $event->id . '/delete');
            $nestedData['action'] = "<button onclick=\"deleteEvent({$event->id})\" class=\"btn btn-danger mx-2\">Delete</button>";

            $data[] = $nestedData;
        }

        return response()->json([
            'draw' => intval($request->draw),
            'recordsTotal' => $totalData,
            'recordsFiltered' => $totalFiltered,
            'data' => $data
        ]);
    }

    public function getById($id)
    {
        $event =  Notification::with('camera')->findOrFail($id);
        return response()->json($event, 200);
    }

    public function getEventPosition($id)
    {
        $event = Notification::findOrFail($id);

        $position = Notification::where('start_error_time', '>=', $event->start_error_time)
            ->orderBy('start_error_time', 'desc')
            ->count();

        return response()->json([
            'position' => $position,
            'page' => ceil($position / 10)
        ]);
    }

    public function create()
    {
        return view('events.create');
    }

    public function store(Request $request)
    {
        $status = $request->status == True ? 1 : 0;
        $today = Carbon::now();
        $monotonicTime = Carbon::createFromTimestamp($request->time);
        $timestamp = Carbon::create(
            $today->year,
            $today->month,
            $today->day,
            $today->hour,
            $today->minute,
            $monotonicTime->second
        );

        $data = [
            'start_error_time' => $timestamp,
            'url' => $request->url,
            'status' => $status,
            'camera_id' => $request->camera_id,
        ];

        $event = Notification::create($data);
        $event->load('camera');
        event(new CameraEventUpdated($event));

        response()->json($event, 200);
    }

    public function edit($eventId)
    {
        $event = Notification::findOrFail($eventId);

        return view('events.edit', [
            'event' => $event,
        ]);
    }

    public function update(Request $request)
    {
        $event = Notification::where('camera_id', '=', $request->camera_id)
            ->orderBy('created_at', 'desc')
            ->first();
        $today = Carbon::now();
        $monotonicTime = Carbon::createFromTimestamp($request->time);
        $timestamp = Carbon::create(
            $today->year,
            $today->month,
            $today->day,
            $today->hour,
            $today->minute,
            $monotonicTime->second
        );

        $data = [
            'end_error_time' => $timestamp,
            'status' => 0,
        ];

        $event->update($data);

        return response()->json("Successfully update event", 200);
    }

    public function destroy($eventId)
    {
        $event = Notification::findOrFail($eventId);
        $imagePath = urlencode($event->url);
        $response = Http::post("http://cameracontrol:5000/delete-event-image/{$imagePath}");

        if ($response->successful() && $response->json('success')) {
            $event->delete();
            return response()->json(['success' => true, 'message' => 'Event deleted successfully.']);
        }

        if ($response->status() === 404) {
            $event->delete();
            return response()->json(['success' => true, 'message' => 'Event deleted (Image not found).']);
        }

        return response()->json(['success' => false, 'message' => 'Failed to delete image: ' . $response->json('error', 'Unknown error')]);
    }
}