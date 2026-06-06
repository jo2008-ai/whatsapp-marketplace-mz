<?php

namespace App\Console\Commands;

use App\Mail\TrialExpirandoMail;
use App\Mail\TrialExpiradoMail;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class VerificarTrials extends Command
{
    protected $signature = 'marketplace:verificar-trials';

    protected $description = 'Verificar trials expirados e enviar avisos';

    public function handle(): int
    {
        $this->enviarAvisos3Dias();
        $this->enviarAviso1Dia();
        $this->suspenderExpirados();

        return self::SUCCESS;
    }

    private function enviarAvisos3Dias(): void
    {
        $tenants = Tenant::where('estado', 'trial')
            ->where('trial_aviso_3d', false)
            ->where('trial_termina_em', '>', now())
            ->where('trial_termina_em', '<=', now()->addDays(3))
            ->get();

        foreach ($tenants as $tenant) {
            $dono = $tenant->users()->first();

            if (!$dono) {
                continue;
            }

            $diasRestantes = (int) now()->diffInDays($tenant->trial_termina_em);

            try {
                Mail::to($dono->email)->send(
                    new TrialExpirandoMail($tenant, $diasRestantes)
                );

                $tenant->update(['trial_aviso_3d' => true]);

                Log::info("Aviso trial 3 dias enviado", [
                    'tenant_id' => $tenant->id,
                    'email' => $dono->email,
                    'dias_restantes' => $diasRestantes,
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao enviar aviso trial 3 dias", [
                    'tenant_id' => $tenant->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        if ($tenants->count() > 0) {
            $this->info("Avisos 3 dias enviados: {$tenants->count()}");
        }
    }

    private function enviarAviso1Dia(): void
    {
        $tenants = Tenant::where('estado', 'trial')
            ->where('trial_aviso_1d', false)
            ->where('trial_termina_em', '>', now())
            ->where('trial_termina_em', '<=', now()->addDay())
            ->get();

        foreach ($tenants as $tenant) {
            $dono = $tenant->users()->first();

            if (!$dono) {
                continue;
            }

            try {
                Mail::to($dono->email)->send(
                    new TrialExpirandoMail($tenant, 1)
                );

                $tenant->update(['trial_aviso_1d' => true]);

                Log::info("Aviso trial 1 dia enviado", [
                    'tenant_id' => $tenant->id,
                    'email' => $dono->email,
                ]);
            } catch (\Exception $e) {
                Log::error("Erro ao enviar aviso trial 1 dia", [
                    'tenant_id' => $tenant->id,
                    'erro' => $e->getMessage(),
                ]);
            }
        }

        if ($tenants->count() > 0) {
            $this->info("Avisos 1 dia enviados: {$tenants->count()}");
        }
    }

    private function suspenderExpirados(): void
    {
        $expirados = Tenant::where('estado', 'trial')
            ->where('trial_termina_em', '<=', now())
            ->get();

        foreach ($expirados as $tenant) {
            $dono = $tenant->users()->first();

            $tenant->update(['estado' => 'suspenso']);

            if ($dono) {
                try {
                    Mail::to($dono->email)->send(
                        new TrialExpiradoMail($tenant)
                    );
                } catch (\Exception $e) {
                    Log::error("Erro ao enviar email trial expirado", [
                        'tenant_id' => $tenant->id,
                        'erro' => $e->getMessage(),
                    ]);
                }
            }

            Log::info("Trial expirado - tenant suspenso", [
                'tenant_id' => $tenant->id,
                'nome' => $tenant->nome_loja,
            ]);
        }

        if ($expirados->count() > 0) {
            $this->info("Trials expirados: {$expirados->count()} lojas suspensas");
        }
    }
}
