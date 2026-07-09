<?php

namespace App\Console\Commands;

use App\Models\InstanciaWhatsApp;
use App\Services\EvolutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class WahaFixWebhooks extends Command
{
    protected $signature = 'waha:fix-webhooks {--tenant=}';

    protected $description = 'Delete and recreate Evolution API sessions to fix stale webhook URLs';

    private EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        parent::__construct();
        $this->evolutionService = $evolutionService;
    }

    public function handle(): int
    {
        $url = config('services.evolution.url');

        $this->info("Evolution API URL: {$url}");

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
                $estado = $this->evolutionService->obterEstado($inst->tenant_id, $url);

                if ($estado === 'NOT_FOUND') {
                    $this->warn('  Session not found on Evolution API, recreating...');
                    $this->evolutionService->criarInstancia($inst->tenant_id, $url);
                    $fixed++;

                    continue;
                }

                $this->info("  Session state: {$estado}");
            } catch (\Exception $e) {
                $this->error("  Error: {$e->getMessage()}");
                Log::error('evolution:fix-webhooks: error', [
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
