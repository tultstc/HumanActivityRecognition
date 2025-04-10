<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use App\Models\Label;

class LabelController extends Controller
{
    public function index()
    {
        $cameras = Camera::where('status', '=', 1)->orderBy('name')->get();
        return view('tools.label.index', ['cameras' => $cameras]);
    }

    public function labelVideo()
    {
        $labels = Label::where('type', '=', 'action')->orderBy('name')->get();

        return view('tools.label.label-video', compact('labels'));
    }
}