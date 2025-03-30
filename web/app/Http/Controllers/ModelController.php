<?php

namespace App\Http\Controllers;

use App\Models\AiModel;

class ModelController extends Controller
{
    public function getAll()
    {
        $cameras = AiModel::active()->get();
        return response()->json($cameras, 200);
    }
}