<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Notifications\WhatsAppDesconectadoNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WahaWebhookController extends Controller
{
    public function processar(Request $request): JsonResponse
    {
        $data = $request->all();
        $event = $data['event'] ?? null;
        $instance = $data['instance'] ?? $data['session'] ?? 'default';

        if (!$event) {
            return response()->json(['status' => 'ignored']);
        }

        Log::info("Evolution API webhook recebido", [
            'event' => $event,
            'instance' => $instance,
        ]);

        $instancia = InstanciaWhatsApp::where('waha_session', $instance)->first();

        if (!$instancia) {
            Log::warning("Sessao nao encontrada", ['instance' => $instance]);
            return response()->json(['status' => 'session_not_found']);
        }

        match ($event) {
            'connection.update' => $this->processarConnectionUpdate($instancia, $data),
            'messages.upsert' => null,
            default => null,
        };

        return response()->json(['status' => 'processed']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function processarConnectionUpdate(
        InstanciaWhatsApp $instancia,
        array $data
    ): void {
        $payload = $data['data'] ?? $data['payload'] ?? [];
        $state = $payload['state'] ?? null;
        if (!$state) return;

        $estadoAnterior = $instancia->estado;
        $novoEstado = match ($state) {
            'open' => 'conectada',
            'close' => 'desconectada',
            'connecting' => 'aguarda_qr',
            default => $instancia->estado,
        };

        if ($novoEstado === $estadoAnterior) return;

        $instancia->update([
            'estado' => $novoEstado,
            'conectada_em' => $novoEstado === 'conectada' ? now() : null,
            'numero_whatsapp' => $novoEstado === 'conectada'
                ? ($instancia->numero_whatsapp ?? $payload['user'] ?? null)
                : $instancia->numero_whatsapp,
        ]);

        Log::info("Estado WhatsApp actualizado via Evolution API", [
            'instancia' => $instancia->waha_session,
            'de' => $estadoAnterior,
            'para' => $novoEstado,
        ]);

        if ($novoEstado === 'desconectada' && $estadoAnterior === 'conectada') {
            $this->notificarDesconectado($instancia);
        }
    }

    private function notificarDesconectado(InstanciaWhatsApp $instancia): void
    {
        $tenant = $instancia->tenant;
        if (!$tenant) return;

        /** @var \App\Models\User|null $dono */
        $dono = $tenant->users()->first();
        if (!$dono) return;

        try {
            /** @var Tenant $tenant */
            $dono->notify(new WhatsAppDesconectadoNotification(
                $tenant,
                $instancia,
                config('app.url') . '/painel/whatsapp'
            ));
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificacao de desconexao", [
                'instancia' => $instancia->waha_session,
                'erro' => $e->getMessage(),
            ]);
        }
    }
}
