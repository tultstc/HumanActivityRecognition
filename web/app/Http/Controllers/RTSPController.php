<?php

namespace App\Http\Controllers;

use App\Models\Label;
use Illuminate\Http\Request;
use Exception;

class RTSPController extends Controller
{
    private $pidFile;
    private $recordingsPath;

    public function __construct()
    {
        $this->pidFile = storage_path('app/ffmpeg.pid');
        $this->recordingsPath = storage_path('./app/public');
    }

    public function index()
    {
        $labels = Label::where('type', '=', 'action')->orderBy('name')->get();
        return view('tools.record-video.index', compact('labels'));
    }

    public function startRecord(Request $request)
    {
        try {
            $request->validate([
                'rtsp_url' => 'required|string'
            ]);

            if (file_exists($this->pidFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Recording is already in progress'
                ], 400);
            }

            $rtspUrl = $request->input('rtsp_url');
            $outputFile = $this->recordingsPath . '/' . time() . '.mp4';

            $command = sprintf(
                'ffmpeg -fflags +genpts -rtsp_transport tcp -i "%s" -c copy -movflags +faststart -f mp4 "%s" > /dev/null 2>&1 & echo $!',
                $rtspUrl,
                $outputFile
            );

            $pid = exec($command);

            if (!$pid) {
                throw new Exception('Failed to start FFmpeg process');
            }

            $processInfo = [
                'pid' => (int)$pid,
                'command' => $command,
                'output_file' => $outputFile,
                'start_time' => time()
            ];

            file_put_contents($this->pidFile, json_encode($processInfo));

            return response()->json([
                'success' => true,
                'message' => 'Recording started successfully',
                'pid' => $pid
            ]);
        } catch (Exception $e) {
            if (file_exists($this->pidFile)) {
                unlink($this->pidFile);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to start recording: ' . $e->getMessage()
            ], 500);
        }
    }

    public function stopRecord()
    {
        try {
            if (!file_exists($this->pidFile)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active recording found'
                ], 400);
            }

            $processInfo = json_decode(file_get_contents($this->pidFile), true);
            $pid = $processInfo['pid'];

            if (!$pid) {
                throw new Exception('Invalid PID in process file');
            }

            exec("kill -TERM $pid");
            $maxWaitTime = 1;
            $waited = 0;

            while ($waited < $maxWaitTime) {
                exec("ps -p $pid", $output);
                if (count($output) <= 1) {
                    break;
                }
                usleep(500000);
                $waited += 0.5;
            }

            if ($waited >= $maxWaitTime) {
                exec("kill -9 $pid");
            }

            $outputFile = $processInfo['output_file'];
            $filename = basename($outputFile);
            $publicUrl = asset('storage/' . $filename);

            unlink($this->pidFile);
            return response()->json([
                'success' => true,
                'message' => 'Recording stopped successfully',
                'output_url' => $publicUrl,
                'filename' => $filename
            ]);
        } catch (Exception $e) {
            if (file_exists($this->pidFile)) {
                unlink($this->pidFile);
            }
            return response()->json([
                'success' => false,
                'message' => 'Failed to stop recording: ' . $e->getMessage()
            ], 500);
        }
    }
}