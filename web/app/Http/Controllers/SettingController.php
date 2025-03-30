<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class SettingController extends Controller
{
    public function updateEventCleanupPeriod(Request $request)
    {
        $request->validate([
            'period' => 'required|in:5,15,30,60,1,3,5,10,30,60,90',
        ]);

        $lastCleanup = now();

        $setting = Setting::updateOrCreate(
            ['key' => 'events_cleanup_period'],
            [
                'value' => $request->period,
                'last_cleanup_at' => $lastCleanup
            ]
        );

        $nextCleanup = $this->calculateNextCleanup(
            $lastCleanup,
            (int)$request->period,
        );

        return response()->json([
            'message' => 'Settings updated successfully',
            'period' => $request->period,
            'next_cleanup' => $nextCleanup->format('h:i A d/m/Y')
        ]);
    }

    public function getEventCleanupPeriod()
    {
        try {
            $setting = Setting::where('key', 'events_cleanup_period')->first();

            if (!$setting) {
                return response()->json([
                    'period' => null,
                    'next_cleanup' => null
                ]);
            }

            $nextCleanup = $this->calculateNextCleanup(
                $setting->last_cleanup_at,
                (int)$setting->value,
            );

            return response()->json([
                'period' => $setting->value,
                'last_cleanup' => $setting->last_cleanup_at->format('h:i A d/m/Y'),
                'next_cleanup' => $nextCleanup->format('h:i A d/m/Y')
            ]);
        } catch (\Exception $e) {
            Log::error('Error in getEventCleanupPeriod:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch settings: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateNextCleanup(Carbon $lastCleanup, int $period): Carbon
    {
        $nextCleanup = $lastCleanup->copy();

        $nextCleanup = match ($period) {
            1 => $nextCleanup->addDay(),
            3 => $nextCleanup->addDays(3),
            5 => $nextCleanup->addDays(5),
            10 => $nextCleanup->addDays(10),
            30 => $nextCleanup->addMonth(),
            60 => $nextCleanup->addMonths(2),
            90 => $nextCleanup->addMonths(3),
        };

        return $nextCleanup->startOfDay();
    }
}