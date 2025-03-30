<?php

namespace App\Http\Controllers;

use App\Exceptions\CustomException;
use App\Models\Area;
use App\Models\Workshop;
use Illuminate\Http\Request;

class WorkshopController extends Controller
{
    public function index()
    {
        $workshops = Workshop::with('area')->get(); // are is a function in Workshop Model
        return view('locations.workshops.index', ['workshops' => $workshops]);
    }

    public function create()
    {
        $areas = Area::select('ten', 'id')->get();
        return view('locations.workshops.create', ['areas' => $areas]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'ten' => 'required|string|max:255',
            'ma' => 'max:255|unique:khuvuc,ma',
            'mota' => 'string|max:255',
        ]);
        $workshop = Workshop::create([
            'ten' => $request->ten,
            'ma' => $request->ma,
            'mota' => $request->mota,
            'khuvucid' => $request->area
        ]);

        return redirect('/locations/workshops')->with('status', 'Workshop created successfully');
    }

    public function edit($workshopId)
    {
        $workshop = Workshop::with('area')->findOrFail($workshopId);
        $oldArea = $workshop->area;
        $areas = Area::get();

        return view('locations.workshops.edit', [
            'workshop' => $workshop,
            'areas' => $areas,
            'oldArea' => $oldArea
        ]);
    }

    public function update(Request $request, $workshopId)
    {
        $workshop = Workshop::findOrFail($workshopId);
        $request->validate([
            'ten' => 'string|max:255',
            'mota' => 'string|max:255',
        ]);
        $data = [
            'ten' => $request->ten,
            'mota' => $request->mota,
            'khuvucid' => $request->area
        ];

        if ($request->ma != $workshop->ma) {
            $request->validate([
                'ten' => 'string|max:255',
                'ma' => 'string|max:255|unique:khuvuc,ma',
                'mota' => 'string|max:255',
            ]);

            $data += [
                'ma' => $request->ma,
            ];
        }

        $workshop->update($data);

        return redirect('/locations/workshops')->with('status', 'Workshop Updated Successfully');
    }

    public function destroy($workshopId)
    {
        $workshop = Workshop::findOrFail($workshopId);
        $workshop->delete();

        return redirect('/locations/workshops')->with('status', 'Workshop Delete Successfully');
    }
}
