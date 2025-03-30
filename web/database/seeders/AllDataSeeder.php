<?php

namespace Database\Seeders;

use App\Models\AiModel;
use App\Models\Area;
use App\Models\Camera;
use App\Models\Group;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class AllDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create Permissions
        Permission::create(['name' => 'view user']);
        Permission::create(['name' => 'create user']);
        Permission::create(['name' => 'update user']);
        Permission::create(['name' => 'delete user']);

        Permission::create(['name' => 'view role']);
        Permission::create(['name' => 'create role']);
        Permission::create(['name' => 'update role']);
        Permission::create(['name' => 'delete role']);

        Permission::create(['name' => 'view camera']);
        Permission::create(['name' => 'create camera']);
        Permission::create(['name' => 'update camera']);
        Permission::create(['name' => 'delete camera']);

        Permission::create(['name' => 'view event']);
        Permission::create(['name' => 'create event']);
        Permission::create(['name' => 'update event']);
        Permission::create(['name' => 'delete event']);

        // Create Roles
        $admin = Role::create(['name' => 'admin']);
        $normal = Role::create(['name' => 'normal']);

        // Create Models
        AiModel::create(['id' => 1, 'name' => 'Model Default', 'url' => 'model/yolo11n.pt', 'status' => 1, 'config' => json_encode(['conf' => 0.5, 'label_conf' => [0]])]);
        AiModel::create(['id' => 2, 'name' => 'Model Pose', 'url' => 'model/yolo11n-pose.pt', 'status' => 1, 'config' => json_encode(['conf' => 0.5, 'label_conf' => [0]])]);
        AiModel::create(['id' => 3, 'name' => 'Model Count', 'url' => 'model/yolo11n.pt', 'status' => 1, 'config' => json_encode(['conf' => 0.5, 'label_conf' => [0]])]);
        AiModel::create(['id' => 4, 'name' => 'Model Tracking', 'url' => 'model/yolo11n.pt', 'status' => 1, 'config' => json_encode(['conf' => 0.5, 'label_conf' => [0]])]);

        // Create Cameras
        Camera::create(['id' => 1, 'name' => 'Camera 1', 'stream_url' => 'rtsp://cam180:Stc@2024@192.168.8.180:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 2, 'name' => 'Camera 2', 'stream_url' => 'rtsp://cam181:Stc@2024@192.168.8.181:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 3, 'name' => 'Camera 3', 'stream_url' => 'rtsp://cam182:Stc@2024@192.168.8.182:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 4, 'name' => 'Camera 4', 'stream_url' => 'rtsp://cam183:Stc@2024@192.168.8.183:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 5, 'name' => 'Camera 5', 'stream_url' => 'rtsp://cam184:Stc@2024@192.168.8.184:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 6, 'name' => 'Camera 6', 'stream_url' => 'rtsp://cam185:Stc@2024@192.168.8.185:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 7, 'name' => 'Camera 7', 'stream_url' => 'rtsp://cam186:Stc@2024@192.168.8.186:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 8, 'name' => 'Camera 8', 'stream_url' => 'rtsp://cam187:Stc@2024@192.168.8.187:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 9, 'name' => 'Camera 9', 'stream_url' => 'rtsp://cam188:Stc@2024@192.168.8.188:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 10, 'name' => 'Camera 10', 'stream_url' => 'rtsp://cam189:Stc@2024@192.168.8.189:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 11, 'name' => 'Camera 11', 'stream_url' => 'rtsp://cam190:Stc@2024@192.168.8.190:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 12, 'name' => 'Camera 12', 'stream_url' => 'rtsp://cam191:Stc@2024@192.168.8.191:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 13, 'name' => 'Camera 13', 'stream_url' => 'rtsp://cam192:Stc@2024@192.168.8.192:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 14, 'name' => 'Camera 14', 'stream_url' => 'rtsp://cam193:Stc@2024@192.168.8.193:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 15, 'name' => 'Camera 15', 'stream_url' => 'rtsp://cam194:Stc@2024@192.168.8.194:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 16, 'name' => 'Camera 16', 'stream_url' => 'rtsp://cam195:Stc@2024@192.168.8.195:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 17, 'name' => 'Camera 17', 'stream_url' => 'rtsp://cam196:Stc@2024@192.168.8.196:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 18, 'name' => 'Camera 18', 'stream_url' => 'rtsp://cam197:Stc@2024@192.168.8.197:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 19, 'name' => 'Camera 19', 'stream_url' => 'rtsp://cam198:Stc@2024@192.168.8.198:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 20, 'name' => 'Camera 20', 'stream_url' => 'rtsp://cam199:Stc@2024@192.168.8.199:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);
        Camera::create(['id' => 21, 'name' => 'Camera 21', 'stream_url' => 'rtsp://cam200:Stc@2024@192.168.8.200:554/profile1', 'model_id' => 1, 'status' => 0, 'config' => json_encode(['fps' => 5, 'maxoutframe' => 10, 'mininframe' => 10])]);

        // Give all permission to admin role.
        $allPermissionNames = Permission::pluck('name')->toArray();

        $admin->givePermissionTo($allPermissionNames);

        // Give few permissions to normal role.
        $normal->givePermissionTo(['view event']);


        // Create User and assign Role to it.

        $adminUser = User::firstOrCreate([
            'email' => 'admin@gmail.com',
        ], [
            'name' => 'Admin',
            'password' => Hash::make('Stc@2024'),
        ]);

        $adminUser->assignRole($admin);


        $normalUser = User::firstOrCreate([
            'email' => 'normal@gmail.com'
        ], [
            'name' => 'Normal',
            'password' => Hash::make('Stc@2024'),
        ]);

        $normalUser->assignRole($normal);
    }
}