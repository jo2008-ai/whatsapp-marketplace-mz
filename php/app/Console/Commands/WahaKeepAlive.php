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

    protected $description = 'Ping WAHA, recreate missing sessions, fix stale webhooks, prevent Render spin-down';

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
            $this->error('WAHA_URL nao configurada');

            return self::FAILURE;
        }

        $key = config('services.waha.key', '');
        $headers = ['X-Api-Key' => $key];
        $expectedWebhookBase = config('services.waha.webhook_base_url', 'http://localhost').'/api/waha/webhook/';

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
                Log::info('WAHA keep-alive', ['url' => $url, 'sessions' => $count]);

                $instancias = InstanciaWhatsApp::where('estado', 'conectada')
                    ->orWhere('estado', 'aguarda_qr')
                    ->get();

                $recreated = 0;
                $fixed = 0;

                foreach ($instancias as $inst) {
                    if (! in_array($inst->waha_session, $activeNames)) {
                        $this->warn("Sessao {$inst->waha_session} perdida, a recriar...");

                        $this->wahaService->criarInstancia($inst->tenant_id, $url);
                        $this->wahaService->ligar($inst->tenant_id, $url);
                        $recreated++;

                        continue;
                    }

                    // Check webhook URL for active sessions
                    try {
                        $sessionResp = Http::withHeaders($headers)
                            ->timeout(30)
                            ->get("{$url}/api/sessions/{$inst->waha_session}");

                        if ($sessionResp->successful()) {
                            $sessionData = $sessionResp->json();
                            $webhooks = $sessionData['config']['webhooks'] ?? [];
                            $currentWebhook = $webhooks[0]['url'] ?? null;
                            $correctWebhook = $expectedWebhookBase.$inst->tenant_id;

                            if ($currentWebhook && $currentWebhook !== $correctWebhook) {
                                $this->warn("Sessao {$inst->waha_session} webhook desactualizado: {$currentWebhook}");
                                $this->info("  Corrigindo para: {$correctWebhook}");

                                $this->wahaService->desligar($inst->tenant_id, $url);
                                $this->wahaService->apagarInstancia($inst->tenant_id, $url);
                                $this->wahaService->criarInstancia($inst->tenant_id, $url);
                                $this->wahaService->ligar($inst->tenant_id, $url);

                                $fixed++;
                                Log::info('WAHA keep-alive: webhook corrigido', [
                                    'session' => $inst->waha_session,
                                    'old' => $currentWebhook,
                                    'new' => $correctWebhook,
                                ]);
                            }
                        }
                    } catch (\Exception $e) {
                        Log::warning('WAHA keep-alive: falha ao verificar webhook', [
                            'session' => $inst->waha_session,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }

                if ($recreated > 0 || $fixed > 0) {
                    $parts = [];
                    if ($recreated > 0) {
                        $parts[] = "{$recreated} recriada(s)";
                    }
                    if ($fixed > 0) {
                        $parts[] = "{$fixed} webhook(s) corrigido(s)";
                    }
                    $this->info('Sessoes: '.implode(', ', $parts));
                    Log::info('WAHA keep-alive: accoes', ['recreated' => $recreated, 'fixed' => $fixed]);
                }
            } else {
                $this->warn("WAHA: {$url} -> HTTP {$resp->status()}");
                Log::warning('WAHA keep-alive failed', ['url' => $url, 'status' => $resp->status()]);
            }
        } catch (\Exception $e) {
            $this->error("WAHA: {$url} -> {$e->getMessage()}");
            Log::error('WAHA keep-alive error', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
