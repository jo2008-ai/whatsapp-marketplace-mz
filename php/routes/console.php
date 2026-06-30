<?php

use Illuminate\Support\Facades\Schedule;

Schedule::job(new \App\Jobs\LimparSessoesExpiradasJob)->dailyAt('03:00');
Schedule::command('waha:keep-alive')->everyTenMinutes();
