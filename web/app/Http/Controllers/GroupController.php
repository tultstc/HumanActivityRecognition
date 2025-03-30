<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class GroupController extends Controller
{
    public function index()
    {
        $groups = Cache::remember('groups', Carbon::now()->addMinutes(30), function () {
            return Group::get();
        });
        return view('configurations.groups.index', ['groups' => $groups]);
    }

    public function create()
    {
        return view('configurations.groups.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:khuvuc,chucnang'
        ]);

        $data = [
            'ten' => $request->name,
            'mota' => $request->description,
            'loainhom' => $request->type
        ];

        Group::create($data);

        return redirect(route('configurations.groups'))->with('status', 'Group created successfully');
    }

    public function edit($groupId)
    {
        $group = Group::findOrFail($groupId);

        return view('configurations.groups.edit', [
            'group' => $group,
        ]);
    }

    public function update(Request $request, $groupId)
    {
        $group = Group::findOrFail($groupId);
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:255',
            'type' => 'required|in:khuvuc,chucnang'
        ]);
        $data = [
            'ten' => $request->name,
            'mota' => $request->description,
            'loainhom' => $request->type
        ];

        $group->update($data);

        return redirect(route('configurations.groups'))->with('status', 'Group Updated Successfully');
    }

    public function destroy($groupId)
    {
        $group = Group::findOrFail($groupId);
        $group->delete();

        return redirect('/configurations/groups')->with('status', 'Group Delete Successfully');
    }
}
