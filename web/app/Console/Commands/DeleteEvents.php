<?php

namespace App\Console\Commands;

use App\Models\Notification;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class DeleteEvents extends Command
{
    protected $signature = 'app:delete-events';
    protected $description = 'Delete all events and related data based on configured time period';

    public function handle()
    {
        try {
            Log::info("Starting delete events command...");
            $this->info("Starting delete events command...");

            $setting = Setting::where('key', 'events_cleanup_period')->first();

            if (!$setting) {
                Log::info("No cleanup schedule configured");
                $this->info("No cleanup schedule configured");
                return 0;
            }

            Log::info("Current setting: " . json_encode($setting->toArray()));
            $this->info("Current setting: " . json_encode($setting->toArray()));

            $period = (int)$setting->value;
            $lastCleanup = $setting->last_cleanup_at ?
                Carbon::parse($setting->last_cleanup_at) :
                Carbon::parse($setting->created_at ?? now());

            Log::info("Last cleanup: " . $lastCleanup->format('Y-m-d H:i:s'));
            $this->info("Last cleanup: " . $lastCleanup->format('Y-m-d H:i:s'));

            $nextCleanupDate = $this->calculateNextCleanup($lastCleanup, $period);

            Log::info("Next cleanup date: " . ($nextCleanupDate ? $nextCleanupDate->format('Y-m-d H:i:s') : 'null'));
            $this->info("Next cleanup date: " . ($nextCleanupDate ? $nextCleanupDate->format('Y-m-d H:i:s') : 'null'));

            if (!$nextCleanupDate || Carbon::now()->lt($nextCleanupDate)) {
                $message = "Not time to cleanup yet. Next cleanup at: " . $nextCleanupDate;
                Log::info($message);
                $this->info($message);
                return 0;
            }

            $count = Notification::count();
            Notification::truncate();

            $apiUrl = env('DELETE_IMAGES_API_URL', 'http://cameracontrol:5000/delete-all-images');
            $response = Http::delete($apiUrl);

            if ($response->ok()) {
                $this->info("Images deleted successfully: " . $response->json()['message']);
                Log::info("Images deleted successfully.");
            } else {
                $errorMessage = "Failed to delete images. API response: " . $response->body();
                $this->error($errorMessage);
                Log::error($errorMessage);
            }

            $setting->update(['last_cleanup_at' => now()]);

            $message = "Events cleanup completed. Deleted {$count} events. Next cleanup will be at: " . $this->calculateNextCleanup(Carbon::now(), $period);
            Log::info($message);
            $this->info($message);

            return 0;
        } catch (\Exception $e) {
            $message = 'Error in events cleanup: ' . $e->getMessage();
            Log::error($message);
            $this->error($message);
            Log::error($e->getTraceAsString());
            return 1;
        }
    }

    private function calculateNextCleanup(Carbon $lastCleanup, int $period): ?Carbon
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
            default => $nextCleanup->addMonths(3),
        };
        if ($nextCleanup) {
            $nextCleanup->startOfDay();
        }

        return $nextCleanup;
    }
}