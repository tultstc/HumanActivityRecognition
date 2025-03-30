<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Group;
use App\Models\Position;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;

class UserRolePermissionSeeder extends Seeder
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