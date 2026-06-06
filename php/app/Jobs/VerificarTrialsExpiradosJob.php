<?php

namespace App\Jobs;

use App\Models\Tenant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerificarTrialsExpiradosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $expirados = Tenant::where('estado', 'trial')
            ->where('trial_termina_em', '<=', now())
            ->get();

        foreach ($expirados as $tenant) {
            $tenant->update(['estado' => 'suspenso']);

            Log::info("Trial expirado - tenant suspenso", [
                'tenant_id' => $tenant->id,
                'nome' => $tenant->nome_loja,
            ]);
        }

        if ($expirados->count() > 0) {
            Log::info("Trials expirados processados: {$expirados->count()} lojas suspensas");
        }
    }
}
