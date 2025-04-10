<?php

namespace App\Http\Controllers;

use App\Models\Camera;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class FacialCollectionController extends Controller
{
    public function index()
    {
        $cameras = Camera::where('status', '=', 1)->orderBy('name')->get();
        return view('tools.facial-collection.index', compact('cameras'));
    }

    public function capture(Request $request)
    {
        $request->validate([
            'camera_id' => 'required|integer',
            'person_name' => 'required|string'
        ]);

        $cameraId = $request->input('camera_id');
        $personName = $request->input('person_name');

        try {
            $response = Http::get("http://cameracontrol:5000/get_snapshot/{$cameraId}");

            if ($response->successful()) {
                $imageData = $response->body();
                $tempPath = storage_path('app/public/temp_faces');

                if (!File::exists($tempPath)) {
                    File::makeDirectory($tempPath, 0755, true);
                }

                $filename = 'face_' . time() . '_' . rand(1000, 9999) . '.jpg';
                $filePath = $tempPath . '/' . $filename;

                File::put($filePath, $imageData);

                $previewUrl = asset('storage/temp_faces/' . $filename);

                return response()->json([
                    'success' => true,
                    'image_path' => $filePath,
                    'preview_url' => $previewUrl
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot take pictures from camera'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function removeImage(Request $request)
    {
        $request->validate([
            'image_path' => 'required|string'
        ]);

        $imagePath = $request->input('image_path');

        if (File::exists($imagePath)) {
            File::delete($imagePath);

            return response()->json([
                'success' => true
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'File not found'
        ], 400);
    }

    public function save(Request $request)
    {
        try {
            $baseDir = storage_path('app/faces');
            if (!File::exists($baseDir)) {
                File::makeDirectory($baseDir, 0755, true);
            }

            $request->validate([
                'person_name' => 'required|string',
                'images' => 'required|array',
                'images.*' => 'required|string'
            ]);

            $personName = $request->input('person_name');
            $images = $request->input('images');
            $personDir = $baseDir . '/' . $personName;

            if (!File::exists($personDir)) {
                File::makeDirectory($personDir, 0755, true);
            }

            $count = 0;
            foreach ($images as $imagePath) {
                if (File::exists($imagePath)) {
                    $filename = 'face_' . time() . '_' . $count . '.jpg';
                    $newPath = $personDir . '/' . $filename;

                    File::copy($imagePath, $newPath);
                    File::delete($imagePath);

                    $count++;
                } else {
                    Log::warning("Image not found: {$imagePath}");
                }
            }

            return response()->json([
                'success' => true,
                'count' => $count,
                'message' => "Saved {$count} photos for {$personName}"
            ]);
        } catch (\Exception $e) {
            Log::error("Error saving faces: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage()
            ], 500);
        }
    }

    public function processDatabase(Request $request)
    {
        try {
            $response = Http::post('http://insightface_api:5000/prepare_database', [
                'db_directory' => './faces',
                'output_path' => 'rest_face_database_test.npy'
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'face_count' => $data['face_count'] ?? 0,
                    'message' => $data['message'] ?? 'Database has been processed successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to process database: ' . ($response['message'] ?? 'Unkown error')
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function extractDatabase(Request $request)
    {
        try {
            $request->validate([
                'database_path' => 'nullable|string'
            ]);

            $databasePath = $request->input('database_path', 'rest_face_database_test.npy');

            $response = Http::post('http://insightface_api:5000/extract_database', [
                'database_path' => $databasePath
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'message' => 'Database extracted successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to extract database: ' . ($response['message'] ?? 'Unknown error')
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function updateDatabase(Request $request)
    {
        try {
            $request->validate([
                'db_directory' => 'nullable|string',
                'database_path' => 'nullable|string',
                'mode' => 'nullable|string|in:merge,replace',
                'update_existing' => 'nullable|boolean'
            ]);

            $dbDirectory = $request->input('db_directory', './faces');
            $databasePath = $request->input('database_path', 'rest_face_database_test.npy');
            $mode = $request->input('mode', 'merge');
            $updateExisting = $request->input('update_existing', false);

            $response = Http::post('http://insightface_api:5000/update_database', [
                'db_directory' => $dbDirectory,
                'database_path' => $databasePath,
                'mode' => $mode,
                'update_existing' => $updateExisting
            ]);

            if ($response->successful()) {
                $data = $response->json();

                return response()->json([
                    'success' => true,
                    'total_faces' => $data['total_faces'] ?? 0,
                    'new_faces_added' => $data['new_faces_added'] ?? 0,
                    'message' => $data['message'] ?? 'Database has been updated successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unable to update database: ' . ($response['message'] ?? 'Unknown error')
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function deletePerson(Request $request)
    {
        try {
            $request->validate([
                'person_name' => 'required|string'
            ]);

            $personName = $request->input('person_name');
            $personDir = storage_path('app/faces/' . $personName);

            if (File::exists($personDir)) {
                // Delete the directory and all its contents
                File::deleteDirectory($personDir);

                return response()->json([
                    'success' => true,
                    'message' => "Successfully deleted all facial data for \"{$personName}\""
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => "Person directory not found for \"{$personName}\""
                ], 404);
            }
        } catch (\Exception $e) {
            Log::error("Error deleting person: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => "Error: " . $e->getMessage()
            ], 500);
        }
    }
}