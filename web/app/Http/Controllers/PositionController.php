<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Position;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Cache::remember('positions', Carbon::now()->addMinutes(30), function () {
            return Position::with('area')->get();
        });
        return view('configurations.positions.index', ['positions' => $positions]);
    }

    public function create()
    {
        $areas = Cache::remember('areas_posi', Carbon::now()->addMinutes(30), function () {
            return Area::select('ten', 'id')->get();
        });
        return view('configurations.positions.create', ['areas' => $areas]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'max:255|unique:khuvuc,ma',
            'description' => 'nullable|string|max:255',
        ]);
        Position::create([
            'ten' => $request->name,
            'ma' => $request->code,
            'mota' => $request->description,
            'khuvucid' => $request->areaId,
        ]);

        return redirect(route('configurations.positions'))->with('status', 'Position created successfully');
    }

    public function edit($positionId)
    {
        $position = Position::with('area')->findOrFail($positionId);

        $oldArea = $position->area;
        $areas = Area::get();
        return view('configurations.positions.edit', [
            'position' => $position,
            'areas' => $areas,
            'oldArea' => $oldArea,
        ]);
    }

    public function update(Request $request, $positionId)
    {
        $position = Position::findOrFail($positionId);
        $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string|max:255',
        ]);
        $data = [
            'ten' => $request->name,
            'mota' => $request->description,
            'khuvucid' => $request->areaId,
        ];

        if ($request->code != $position->ma) {
            $request->validate([
                'name' => 'string|max:255',
                'code' => 'string|max:255|unique:khuvuc,ma',
                'description' => 'nullable|string|max:255',
            ]);

            $data += [
                'ma' => $request->code,
            ];
        }

        $position->update($data);

        return redirect(route('configurations.positions'))->with('status', 'Position Updated Successfully');
    }

    public function destroy($positionId)
    {
        $user = Position::findOrFail($positionId);
        $user->delete();

        return redirect('/configurations/positions')->with('status', 'Position Delete Successfully');
    }
}
