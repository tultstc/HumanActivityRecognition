<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Middleware\PermissionMiddleware;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(PermissionMiddleware::using('view role'), only: ['index']),
            new Middleware(PermissionMiddleware::using('create role'), only: ['create', 'store']),
            new Middleware(PermissionMiddleware::using('update role'), only: ['edit', 'update']),
            new Middleware(PermissionMiddleware::using('delete role'), only: ['destroy']),
        ];
    }

    public function index()
    {

        $roles = Role::all();
        return view('roles.index', compact('roles'));
    }

    public function create()
    {
        $permissions =  Permission::all();
        return view('roles.create', compact('permissions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array'
        ]);

        $role = Role::create([
            'name' => $request->name,
            'guard_name' => 'web'
        ]);

        if ($request->has('permissions')) {
            $role->syncPermissions($request->permissions);
        }

        return redirect(url('/roles'))->with('status', 'Successfully created Role!');
    }

    public function edit(Role $role)
    {
        $permissions = Permission::all();
        $rolePermissions = $role->permissions->pluck('id')->toArray();
        return view('roles.edit', compact('role', 'permissions', 'rolePermissions'));
    }

    public function update(Request $request, Role $role)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update(['name' => $request->name]);
        $role->permissions()->sync($request->permissions);

        return redirect(url('/roles'))->with('status', 'Successfully updated Role!');
    }

    public function destroy($roleId)
    {
        try {
            $role = Role::findOrFail($roleId);
            $role->delete();

            return response()->json([
                'success' => true,
                'message' => 'Role deleted successfully!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the role. ' . $e->getMessage()
            ], 500);
        }
    }
}