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
        $session = $data['session'] ?? 'default';

        if (!$event) {
            return response()->json(['status' => 'ignored']);
        }

        Log::info("WAHA webhook recebido", [
            'event' => $event,
            'session' => $session,
        ]);

        $instancia = InstanciaWhatsApp::where('waha_session', $session)->first();

        if (!$instancia) {
            Log::warning("Sessao WAHA nao encontrada", ['session' => $session]);
            return response()->json(['status' => 'session_not_found']);
        }

        match ($event) {
            'session.status' => $this->processarSessionStatus($instancia, $data),
            'session.qr' => $this->processarSessionQr($instancia, $data),
            default => null,
        };

        return response()->json(['status' => 'processed']);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function processarSessionStatus(
        InstanciaWhatsApp $instancia,
        array $data
    ): void {
        $payload = $data['payload'] ?? $data['data'] ?? [];
        $status = $payload['status'] ?? null;
        if (!$status) return;

        $estadoAnterior = $instancia->estado;
        $novoEstado = match ($status) {
            'WORKING' => 'conectada',
            'FAILED', 'STOPPED', 'DISCONNECTED' => 'desconectada',
            'STARTING', 'SCAN_QR_CODE' => 'aguarda_qr',
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

        Log::info("Estado WhatsApp actualizado via WAHA", [
            'instancia' => $instancia->waha_session,
            'de' => $estadoAnterior,
            'para' => $novoEstado,
        ]);

        if ($novoEstado === 'desconectada' && $estadoAnterior === 'conectada') {
            $this->notificarDesconectado($instancia);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function processarSessionQr(
        InstanciaWhatsApp $instancia,
        array $data
    ): void {
        if ($instancia->estado !== 'aguarda_qr') {
            $instancia->update(['estado' => 'aguarda_qr']);
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
