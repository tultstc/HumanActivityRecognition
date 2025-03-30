<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();


Schedule::command('app:delete-events')
    ->timezone('Asia/Ho_Chi_Minh')
    ->at('00:00')
    ->runInBackground()
    ->withoutOverlapping()
    ->appendOutputTo(storage_path('logs/scheduler.log'));