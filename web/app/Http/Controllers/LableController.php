<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LableController extends Controller
{
    public function index()
    {
        return view('tools.label.index');
    }
}