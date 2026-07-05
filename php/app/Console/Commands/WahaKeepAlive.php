<?php

namespace App\Console\Commands;

use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaKeepAlive extends Command
{
    protected $signature = 'waha:keep-alive';
    protected $description = 'Ping WAHA instance to prevent Render free tier spin-down';

    private WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        parent::__construct();
        $this->wahaService = $wahaService;
    }

    public function handle(): int
    {
        $url = config('services.waha.url');

        if (!$url) {
            $this->error("WAHA_URL nao configurada");
            return self::FAILURE;
        }

        $key = config('services.waha.key', '');
        $headers = ['X-Api-Key' => $key];

        try {
            $resp = Http::withHeaders($headers)
                ->timeout(30)
                ->get("{$url}/api/sessions");

            if ($resp->successful()) {
                $sessions = $resp->json();
                $count = is_array($sessions) ? count($sessions) : 0;
                $this->info("WAHA: {$url} -> {$count} sessao(oes) activa(s)");
                Log::info("WAHA keep-alive", ['url' => $url, 'sessions' => $count]);
            } else {
                $this->warn("WAHA: {$url} -> HTTP {$resp->status()}");
                Log::warning("WAHA keep-alive failed", ['url' => $url, 'status' => $resp->status()]);
            }
        } catch (\Exception $e) {
            $this->error("WAHA: {$url} -> {$e->getMessage()}");
            Log::error("WAHA keep-alive error", ['url' => $url, 'error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
