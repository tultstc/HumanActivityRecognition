<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LabelManagementController extends Controller
{
    public function index()
    {
        $labels = Label::orderBy('name')->get();
        return view('tools.label-management.index', compact('labels'));
    }

    public function create()
    {
        return view('tools.label-management.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:labels,name',
            'type' => 'required|in:action,object',
            'status' => 'required|in:0,1',
        ]);

        try {
            Label::create([
                'name' => $request->name,
                'type' => $request->type,
                'status' => $request->status,
            ]);
            return redirect()->route('label-management')->with('status', 'Successfully created Label!');
        } catch (Exception $e) {
            return redirect()->route('label-management')->with('status', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($labelId)
    {
        $label = Label::findOrFail($labelId);
        return view('tools.label-management.edit', compact('label'));
    }

    public function update(Request $request, $labelId)
    {
        $request->validate([
            'name' => 'required',
            'type' => 'required|in:action,object',
            'status' => 'required|in:0,1',
        ]);

        try {
            $label = Label::findOrFail($labelId);

            $label->update([
                'name' => $request->name,
                'type' => $request->type,
                'status' => $request->status,
            ]);

            return redirect()
                ->route('label-management')
                ->with('status', 'Label updated successfully!');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Error updating label: ' . $e->getMessage());
        }
    }

    public function destroy($labelId)
    {
        try {
            $label = Label::findOrFail($labelId);

            $label->delete();

            return response()->json([
                'success' => true,
                'message' => 'Label deleted successfully!'
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete the label. ' . $e->getMessage()
            ], 500);
        }
    }
}