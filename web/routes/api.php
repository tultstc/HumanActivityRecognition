<?php

use App\Events\CameraEventUpdated;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\TrainingController;
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

Route::prefix('train')->group(function () {
    Route::get('/annotation-files', [TrainingController::class, 'getAnnotationFiles']);
    Route::post('/start', [TrainingController::class, 'startTraining']);
    Route::get('/status/{jobId}', [TrainingController::class, 'getTrainingStatus']);
    Route::post('/stop/{jobId}', [TrainingController::class, 'stopTraining']);
    Route::get('/list', [TrainingController::class, 'listTrainingJobs']);
});

Route::prefix('recordings')->group(function () {
    Route::get('/list', 'App\Http\Controllers\RecordingsController@list');
    Route::get('/preview/{path}', 'App\Http\Controllers\RecordingsController@preview')->where('path', '.*');
    Route::get('/stream', 'App\Http\Controllers\RecordingsController@stream');
    Route::get('/metadata', 'App\Http\Controllers\RecordingsController@metadata');
    Route::get('/frame', 'App\Http\Controllers\RecordingsController@frame');
});

Route::prefix('tools/facial-collection')->group(function () {
    Route::post('/capture', 'App\Http\Controllers\FacialCollectionController@capture');
    Route::post('/remove-image', 'App\Http\Controllers\FacialCollectionController@removeImage');
    Route::post('/save', 'App\Http\Controllers\FacialCollectionController@save');
    Route::post('/process-database', 'App\Http\Controllers\FacialCollectionController@processDatabase');
    Route::post('/extract-database', 'App\Http\Controllers\FacialCollectionController@extractDatabase');
    Route::post('/update-database', 'App\Http\Controllers\FacialCollectionController@updateDatabase');
    Route::post('/delete-person', 'App\Http\Controllers\FacialCollectionController@deletePerson');
});