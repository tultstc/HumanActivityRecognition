<?php

use App\Events\CameraEventUpdated;
use App\Http\Controllers\SettingController;
use App\Models\Notification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
Route::get('/models', 'App\Http\Controllers\ModelController@getAll');
Route::get('/cameras', 'App\Http\Controllers\CameraController@getAll');
Route::get('/{id}/rtsp-url', 'App\Http\Controllers\CameraController@getRtspUrl');
Route::post('/alarms', 'App\Http\Controllers\EventController@store');
Route::put('/alarms/update', 'App\Http\Controllers\EventController@update');
Route::get('/alarms/{id}', 'App\Http\Controllers\CameraController@getById');
Route::get('/events', 'App\Http\Controllers\EventController@getData');
Route::get('/settings/event-cleanup', 'App\Http\Controllers\SettingController@getEventCleanupPeriod');
Route::post('/settings/event-cleanup', 'App\Http\Controllers\SettingController@updateEventCleanupPeriod');

Route::post('/test-event', function (Request $request) {
    $status = $request->status == true ? 1 : 0;
    $timestamp = Carbon::now();

    $data = [
        'start_error_time' => $timestamp,
        'url' => $request->url,
        'status' => $status,
        'camera_id' => $request->camera_id,
    ];

    $event = Notification::create($data);
    $event->load('camera');
    event(new CameraEventUpdated($event));

    return response()->json($event);
});