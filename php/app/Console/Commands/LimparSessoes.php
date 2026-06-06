<?php

namespace App\Console\Commands;

use App\Jobs\LimparSessoesExpiradasJob;
use Illuminate\Console\Command;

class LimparSessoes extends Command
{
    protected $signature = 'marketplace:limpar-sessoes';
    protected $description = 'Limpa sessões de bot expiradas (>24h)';

    public function handle(): int
    {
        LimparSessoesExpiradasJob::dispatch();
        $this->info('Job de limpeza de sessões dispatchado.');
        return self::SUCCESS;
    }
}
