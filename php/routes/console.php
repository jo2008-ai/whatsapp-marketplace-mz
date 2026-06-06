<?php

use Illuminate\Support\Facades\Schedule;

// Verificar trials e enviar avisos diariamente às 8h
Schedule::command('marketplace:verificar-trials')->dailyAt('08:00');

// Limpar sessões de bot expiradas diariamente às 3h
Schedule::job(new \App\Jobs\LimparSessoesExpiradasJob)->dailyAt('03:00');

// Relatório semanal de receita (segunda às 8h)
Schedule::command('marketplace:relatorio-receita')->weeklyOn(1, '08:00');
