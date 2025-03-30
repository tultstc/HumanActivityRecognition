<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas =  Area::get();
        return view('configurations.areas.index', ['areas' => $areas]);
    }

    public function create()
    {
        return view('configurations.areas.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'max:255|unique:khuvuc,ma',
            'description' => 'nullable|string|max:255',
        ]);

        $data = [
            'ten' => $request->name,
            'ma' => $request->code,
            'mota' => $request->description,
        ];

        Area::create($data);

        return redirect(route('configurations.areas'))->with('status', 'Area created successfully');
    }

    public function edit($areaId)
    {
        $area = Area::findOrFail($areaId);

        return view('configurations.areas.edit', [
            'area' => $area,
        ]);
    }

    public function update(Request $request, $areaId)
    {
        $area = Area::findOrFail($areaId);
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:255',
        ]);
        $data = [
            'ten' => $request->name,
            'mota' => $request->description,
        ];

        if ($request->code != $area->ma) {
            $request->validate([
                'name' => 'string|max:255',
                'code' => 'string|max:255|unique:khuvuc,ma',
                'description' => 'nullable|string|max:255',
            ]);

            $data += [
                'ma' => $request->code,
            ];
        }

        $area->update($data);

        return redirect(route('configurations.areas'))->with('status', 'Area Updated Successfully');
    }

    public function destroy($areaId)
    {
        $area = Area::findOrFail($areaId);
        $area->delete();

        return redirect('/configurations/areas')->with('status', 'Area Delete Successfully');
    }
}