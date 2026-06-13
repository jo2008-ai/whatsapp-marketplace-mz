<?php

namespace App\Jobs;

use App\Models\SessaoBot;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class LimparSessoesExpiradasJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function handle(): void
    {
        $limite = now()->subHours(24);

        $removidas = SessaoBot::where('updated_at', '<', $limite)
            ->where('estado', '!=', 'inicio')
            ->delete();

        Log::info("Sessões bot limpas: {$removidas} sessões expiradas removidas");
    }
}
