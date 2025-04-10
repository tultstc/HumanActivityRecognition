<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TrainingController extends Controller
{
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = env('TRAINING_API_URL', 'http://posec3d_api:5000');
    }

    public function index()
    {
        return view('tools.train.index');
    }

    public function getAnnotationFiles()
    {
        try {
            $response = Http::get($this->apiBaseUrl . '/api/list-annotation-files');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve annotation files: ' . $e->getMessage()
            ], 500);
        }
    }

    public function startTraining(Request $request)
    {
        try {
            $response = Http::post($this->apiBaseUrl . '/api/train/start', $request->all());

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to training service: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getTrainingStatus($jobId)
    {
        try {
            $response = Http::get($this->apiBaseUrl . '/api/train/status/' . $jobId);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to training service: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stopTraining($jobId)
    {
        try {
            $response = Http::post($this->apiBaseUrl . '/api/train/stop/' . $jobId);

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to training service: ' . $e->getMessage()
            ], 500);
        }
    }

    public function listTrainingJobs()
    {
        try {
            $response = Http::get($this->apiBaseUrl . '/api/train/list');

            return response()->json($response->json());
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to connect to training service: ' . $e->getMessage()
            ], 500);
        }
    }
}