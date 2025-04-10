<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\FacialCollectionController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LabelManagementController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\RecordingsController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\RTSPController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\UserController;

use Illuminate\Support\Facades\Route;

Route::any('/', [AuthController::class, 'login']);

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard/stream/{cameraId}', [DashboardController::class, 'stream']);
    Route::get('/dashboard/cameras', [DashboardController::class, 'getCameras']);
    Route::post('/dashboard/save-preferences', [DashboardController::class, 'savePreferences'])
        ->name('dashboard.save-preferences');
    Route::get('/user/profile', function () {
        return view('profile.show');
    })->name('profile.show');
    Route::get('/user/profile/authentication', function () {
        return view('profile.authentication');
    })->name('profile.authentication');
    Route::get('change', [LanguageController::class, 'change'])->name('lang.change');
    Route::get('/recordings', [RecordingsController::class, 'index'])->name('recordings.index');
    Route::get('tools/facial-collection', [FacialCollectionController::class, 'index'])->name('facial-collection.index');

    Route::group(['middleware' => ['role:admin|normal']], function () {
        Route::get('events', [EventController::class, 'index'])->name('events');
        Route::get('events/data', [EventController::class, 'getEvents'])->name('events.data');
        Route::get('events/{eventId}', [EventController::class, 'getById'])->name('events.get.one');
        Route::get('/events/position/{id}', [EventController::class, 'getEventPosition']);
        Route::get('tools/training', [TrainingController::class, 'index'])->name('train.index');
        Route::get('label-management', [LabelManagementController::class, 'index'])->name('label-management');
        Route::get('label-management/create', [LabelManagementController::class, 'create'])->name('label-management.create');
        Route::post('label-management/store', [LabelManagementController::class, 'store'])->name('label-management.store');
        Route::get('label-management/{labelId}/edit', [LabelManagementController::class, 'edit'])->name('label-management.edit');
        Route::put('label-management/{labelId}', [LabelManagementController::class, 'update'])->name('label-management.update');
        Route::delete('label-management/{labelId}/delete', [LabelManagementController::class, 'destroy'])->name('label-management.destroy');
        Route::get('tools/rtsp/recording', [RTSPController::class, 'index'])->name('rtsp.index');
        Route::post('tools/rtsp/start-record', [RTSPController::class, 'startRecord'])->name('rtsp.start-record');
        Route::post('tools/rtsp/stop-record', action: [RTSPController::class, 'stopRecord'])->name('rtsp.stop-record');
        Route::get('groups', [GroupController::class, 'index'])->name('groups');
        Route::get('groups/create', [GroupController::class, 'create'])->name('groups.create');
        Route::post('groups/store', [GroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{groupId}/edit', [GroupController::class, 'edit'])->name('groups.edit');
        Route::put('groups/{groupId}', [GroupController::class, 'update'])->name('groups.update');
        Route::delete('groups/{groupId}/delete', [GroupController::class, 'destroy'])->name('groups.destroy');
        Route::get('groups/{groupId}/cameras', [GroupController::class, 'getCamerasInGroup'])->name('groups.cameras');
    });


    Route::group(['middleware' => ['role:admin']], function () {
        Route::resource('roles', RoleController::class);
        Route::delete('roles/{roleId}/delete', [RoleController::class, 'destroy']);

        Route::resource('users', UserController::class);
        Route::delete('users/{userId}/delete', [UserController::class, 'destroy']);

        Route::get('cameras', [CameraController::class, 'index'])->name('cameras');
        Route::get('cameras/create', [CameraController::class, 'create'])->name('cameras.create');
        Route::post('cameras/store', [CameraController::class, 'store'])->name('cameras.store');
        Route::get('cameras/{cameraId}/edit', [CameraController::class, 'edit'])->name('cameras.edit');
        Route::put('cameras/{cameraId}', [CameraController::class, 'update'])->name('cameras.update');
        Route::delete('cameras/{cameraId}/delete', [CameraController::class, 'destroy'])->name('cameras.destroy');
        Route::get('cameras/detail', [CameraController::class, 'detail'])->name('cameras.detail');
        Route::get('cameras/{id}', [CameraController::class, 'getByid']);

        Route::get('events/create', [EventController::class, 'create'])->name('events.create');
        Route::post('events/store', [EventController::class, 'store'])->name('events.store');
        Route::get('events/{eventId}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::put('events/{eventId}', [EventController::class, 'update'])->name('events.update');
        Route::delete('events/{eventId}/delete', [EventController::class, 'destroy']);

        Route::get('tools/label', [LabelController::class, 'labelVideo'])->name('label.video');
    });
});