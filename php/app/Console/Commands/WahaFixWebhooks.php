<?php

namespace App\Console\Commands;

use App\Models\InstanciaWhatsApp;
use App\Services\WahaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaFixWebhooks extends Command
{
    protected $signature = 'waha:fix-webhooks {--tenant=}';

    protected $description = 'Delete and recreate WAHA sessions to fix stale webhook URLs';

    private WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        parent::__construct();
        $this->wahaService = $wahaService;
    }

    public function handle(): int
    {
        $url = config('services.waha.url');
        $expectedWebhook = config('services.waha.webhook_base_url', 'http://localhost').'/api/waha/webhook/';

        $this->info("Expected webhook base: {$expectedWebhook}");
        $this->info("WAHA URL: {$url}");

        $query = InstanciaWhatsApp::query();

        if ($tenantId = $this->option('tenant')) {
            $query->where('tenant_id', $tenantId);
        }

        $instancias = $query->get();

        if ($instancias->isEmpty()) {
            $this->warn('No instances found in database.');

            return self::SUCCESS;
        }

        $fixed = 0;

        foreach ($instancias as $inst) {
            $session = $inst->waha_session;
            $this->newLine();
            $this->info("Processing {$session} (tenant {$inst->tenant_id})...");

            try {
                // Get current session config from WAHA
                $resp = Http::withHeaders(['X-Api-Key' => config('services.waha.key')])
                    ->timeout(30)
                    ->get("{$url}/api/sessions/{$session}");

                if ($resp->status() === 404) {
                    $this->warn('  Session not found on WAHA, recreating...');
                    $this->wahaService->criarInstancia($inst->tenant_id, $url);
                    $this->wahaService->ligar($inst->tenant_id, $url);
                    $fixed++;

                    continue;
                }

                if (! $resp->successful()) {
                    $this->error("  Failed to get session: HTTP {$resp->status()}");

                    continue;
                }

                $sessionData = $resp->json();
                $webhooks = $sessionData['config']['webhooks'] ?? [];
                $currentWebhook = $webhooks[0]['url'] ?? null;
                $correctWebhook = $expectedWebhook.$inst->tenant_id;

                if ($currentWebhook === $correctWebhook) {
                    $this->info("  Webhook OK: {$currentWebhook}");

                    continue;
                }

                $this->warn("  Stale webhook: {$currentWebhook}");
                $this->info("  Expected:      {$correctWebhook}");
                $this->info('  Deleting and recreating...');

                // Stop, delete, recreate
                $this->wahaService->desligar($inst->tenant_id, $url);
                $this->wahaService->apagarInstancia($inst->tenant_id, $url);
                $this->wahaService->criarInstancia($inst->tenant_id, $url);
                $this->wahaService->ligar($inst->tenant_id, $url);

                $fixed++;
                $this->info('  Fixed!');
                Log::info('waha:fix-webhooks: fixed session', [
                    'session' => $session,
                    'old_webhook' => $currentWebhook,
                    'new_webhook' => $correctWebhook,
                ]);
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('waha:fix-webhooks: error', [
                    'session' => $session,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $this->newLine();
        $this->info("Done. Fixed {$fixed} session(s).");

        return self::SUCCESS;
    }
}
