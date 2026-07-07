<?php

namespace App\Console\Commands;

use App\Models\InstanciaWhatsApp;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaKeepAlive extends Command
{
    protected $signature = 'waha:keep-alive';
    protected $description = 'Ping WAHA, recreate missing sessions, prevent Render spin-down';

    private WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        parent::__construct();
        $this->wahaService = $wahaService;
    }

    public function handle(): int
    {
        $url = config('services.waha.url');

        if (! $url) {
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
                $activeNames = [];

                if (is_array($sessions)) {
                    foreach ($sessions as $s) {
                        $activeNames[] = $s['name'] ?? $s;
                    }
                }

                $count = count($activeNames);
                $this->info("WAHA: {$url} -> {$count} sessao(oes) activa(s)");
                Log::info("WAHA keep-alive", ['url' => $url, 'sessions' => $count]);

                $instancias = InstanciaWhatsApp::where('estado', 'conectada')
                    ->orWhere('estado', 'aguarda_qr')
                    ->get();

                $recreated = 0;

                foreach ($instancias as $inst) {
                    if (! in_array($inst->waha_session, $activeNames)) {
                        $this->warn("Sessao {$inst->waha_session} perdida, a recriar...");

                        $this->wahaService->criarInstancia($inst->tenant_id, $url);
                        $this->wahaService->ligar($inst->tenant_id, $url);
                        $recreated++;
                    }
                }

                if ($recreated > 0) {
                    $this->info("Recriadas {$recreated} sessao(oes) perdida(s)");
                    Log::info("WAHA keep-alive: sessoes recriadas", ['count' => $recreated]);
                }
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
