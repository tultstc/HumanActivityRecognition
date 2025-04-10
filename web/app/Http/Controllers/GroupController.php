<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Group::with('cameras')->get();
        return view('groups.index', compact('groups'));
    }

    public function getCamerasInGroup(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        $perPage = 6;

        $cameras = $group->cameras()->paginate($perPage);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('groups.partials.camera-list', [
                    'cameras' => $cameras,
                    'group' => $group
                ])->render(),
                'currentPage' => $cameras->currentPage(),
                'lastPage' => $cameras->lastPage()
            ]);
        }

        return $cameras;
    }

    public function create()
    {
        $cameras = Camera::orderBy('name')->get();
        return view('groups.create', compact('cameras'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'cameras' => 'nullable|array',
            'cameras.*' => 'exists:cameras,id',
        ]);

        $groups = Group::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('cameras')) {
            $groups->cameras()->sync($request->cameras);
        }

        return redirect(route('groups'))->with('status', 'Group created successfully');
    }

    public function edit($groupId)
    {
        $group = Group::findOrFail($groupId);
        $cameras = Camera::orderBy('name')->get();

        return view('groups.edit', [
            'group' => $group,
            'cameras' => $cameras,
            'selectedCameras' => $group->cameras->pluck('id')->toArray(),
        ]);
    }

    public function update(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:255',
            'cameras' => 'nullable|array',
            'cameras.*' => 'exists:cameras,id',
        ]);
        $group->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        if ($request->has('cameras')) {
            $group->cameras()->sync($request->cameras);
        } else {
            $group->cameras()->detach();
        }

        return redirect(route('groups'))->with('status', 'Group Updated Successfully');
    }

    public function destroy($groupId)
    {
        try {
            $group = Group::findOrFail($groupId);
            $group->cameras()->detach();
            $group->delete();

            return redirect('/groups')->with('status', 'Group Delete Successfully');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the camera. ' . $e->getMessage()
            ], 500);
        }
    }
}