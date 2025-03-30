<?php

namespace Database\Seeders;

use App\Models\Notification;
use Illuminate\Database\Seeder;

class NotificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:15:45'),
            'end_error_time' => Date('2024-11-14 08:15:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 400,
            'camera_id' => 1,
        ]);
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:16:45'),
            'end_error_time' => Date('2024-11-14 08:16:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 300,
            'camera_id' => 1,
        ]);
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:17:45'),
            'end_error_time' => Date('2024-11-14 08:17:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 400,
            'camera_id' => 1,
        ]);
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:18:45'),
            'end_error_time' => Date('2024-11-14 08:18:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 400,
            'camera_id' => 1,
        ]);
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:19:45'),
            'end_error_time' => Date('2024-11-14 08:19:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 400,
            'camera_id' => 1,
        ]);
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:20:45'),
            'end_error_time' => Date('2024-11-14 08:20:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 400,
            'camera_id' => 1,
        ]);
        Notification::create([
            'start_error_time' => Date('2024-11-14 08:21:45'),
            'end_error_time' => Date('2024-11-14 08:21:55'),
            'description' => 'Detect a person inside restricted zone',
            'image_url' => 'http://localhost:15440/get_snapshot/1',
            'error_code' => 400,
            'camera_id' => 1,
        ]);
    }
}