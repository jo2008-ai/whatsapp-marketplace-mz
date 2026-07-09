<?php

namespace App\Console\Commands;

use App\Models\InstanciaWhatsApp;
use App\Services\EvolutionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaKeepAlive extends Command
{
    protected $signature = 'waha:keep-alive';

    protected $description = 'Ping Evolution API, recreate missing sessions, prevent Render spin-down';

    private EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        parent::__construct();
        $this->evolutionService = $evolutionService;
    }

    public function handle(): int
    {
        $url = config('services.evolution.url');

        if (! $url) {
            $this->error('EVOLUTION_URL nao configurada');

            return self::FAILURE;
        }

        $key = config('services.evolution.key', '');
        $headers = ['apikey' => $key, 'Content-Type' => 'application/json'];

        try {
            $resp = Http::withHeaders($headers)
                ->timeout(30)
                ->get("{$url}/instance/fetchInstances");

            if ($resp->successful()) {
                $instances = $resp->json();
                $activeNames = [];

                if (is_array($instances)) {
                    foreach ($instances as $inst) {
                        $name = $inst['name'] ?? $inst['instanceName'] ?? $inst;
                        if (is_string($name)) {
                            $activeNames[] = $name;
                        }
                    }
                }

                $count = count($activeNames);
                $this->info("Evolution API: {$url} -> {$count} instancia(s) activa(s)");
                Log::info('Evolution keep-alive', ['url' => $url, 'instances' => $count]);

                $instancias = InstanciaWhatsApp::whereIn('estado', ['conectada', 'aguarda_qr', 'desconectada'])
                    ->get();

                $recreated = 0;

                foreach ($instancias as $inst) {
                    if (! in_array($inst->waha_session, $activeNames)) {
                        $this->warn("Sessao {$inst->waha_session} perdida, a recriar...");

                        $this->evolutionService->criarInstancia($inst->tenant_id, $url);
                        $recreated++;

                        continue;
                    }
                }

                if ($recreated > 0) {
                    $this->info("Sessoes: {$recreated} recriada(s)");
                    Log::info('Evolution keep-alive: accoes', ['recreated' => $recreated]);
                }
            } else {
                $this->warn("Evolution API: {$url} -> HTTP {$resp->status()}");
                Log::warning('Evolution keep-alive failed', ['url' => $url, 'status' => $resp->status()]);
            }
        } catch (\Exception $e) {
            $this->error("Evolution API: {$url} -> {$e->getMessage()}");
            Log::error('Evolution keep-alive error', ['url' => $url, 'error' => $e->getMessage()]);
        }

        return self::SUCCESS;
    }
}
