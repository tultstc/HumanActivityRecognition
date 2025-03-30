<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CameraController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\LableController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\RoleController;
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

    Route::group(['middleware' => ['role:admin|normal']], function () {
        Route::get('events', [EventController::class, 'index'])->name('events');
        Route::get('events/data', [EventController::class, 'getEvents'])->name('events.data');
        Route::get('events/{eventId}', [EventController::class, 'getById'])->name('events.get.one');
        Route::get('/events/position/{id}', [EventController::class, 'getEventPosition']);
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

        Route::get('label', [LableController::class, 'index'])->name('label.index');
    });
});