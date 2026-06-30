<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaKeepAlive extends Command
{
    protected $signature = 'waha:keep-alive';
    protected $description = 'Ping all WAHA instances to prevent Render free tier spin-down';

    public function handle(): int
    {
        $urls = [
            1 => config('services.waha.urls.1'),
            2 => config('services.waha.urls.2'),
            3 => config('services.waha.urls.3'),
            4 => config('services.waha.urls.4'),
        ];

        $key = config('services.waha.key', '');
        $headers = ['X-Api-Key' => $key];

        foreach ($urls as $id => $url) {
            if (!$url) {
                $this->warn("WAHA {$id}: sem URL configurada, a saltar...");
                continue;
            }

            try {
                $resp = Http::withHeaders($headers)
                    ->timeout(30)
                    ->get("{$url}/api/default");

                if ($resp->successful()) {
                    $status = $resp->json('status', 'unknown');
                    $this->info("WAHA {$id}: {$url} -> {$status}");
                    Log::info("WAHA keep-alive", ['id' => $id, 'url' => $url, 'status' => $status]);
                } else {
                    $this->warn("WAHA {$id}: {$url} -> HTTP {$resp->status()}");
                    Log::warning("WAHA keep-alive failed", ['id' => $id, 'url' => $url, 'status' => $resp->status()]);
                }
            } catch (\Exception $e) {
                $this->error("WAHA {$id}: {$url} -> {$e->getMessage()}");
                Log::error("WAHA keep-alive error", ['id' => $id, 'url' => $url, 'error' => $e->getMessage()]);
            }
        }

        return self::SUCCESS;
    }
}
