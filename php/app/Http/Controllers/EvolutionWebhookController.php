<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Notifications\WhatsAppDesconectadoNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class EvolutionWebhookController extends Controller
{
    public function processar(Request $request): JsonResponse
    {
        $data = $request->all();

        $event = $data['event'] ?? null;
        $instanceName = $data['instance'] ?? null;

        if (!$event || !$instanceName) {
            return response()->json(['status' => 'ignored']);
        }

        Log::info("Evolution webhook recebido", [
            'event' => $event,
            'instance' => $instanceName,
        ]);

        $instancia = InstanciaWhatsApp::where(
            'evolution_instance_name',
            $instanceName
        )->first();

        if (!$instancia) {
            Log::warning("Instância não encontrada", ['instance' => $instanceName]);
            return response()->json(['status' => 'instance_not_found']);
        }

        match ($event) {
            'connection.update' => $this->processarConnectionUpdate($instancia, $data),
            'qrcode.updated' => $this->processarQrcodeUpdated($instancia, $data),
            'instance.disconnected' => $this->processarDisconnected($instancia, $data),
            default => null,
        };

        return response()->json(['status' => 'processed']);
    }

    private function processarConnectionUpdate(
        InstanciaWhatsApp $instancia,
        array $data
    ): void {
        $state = $data['data']['state'] ?? null;

        if (!$state) {
            return;
        }

        $estadoAnterior = $instancia->estado;

        $novoEstado = match ($state) {
            'open' => 'conectada',
            'close' => 'desconectada',
            'connecting' => 'aguarda_qr',
            default => $instancia->estado,
        };

        if ($novoEstado === $estadoAnterior) {
            return;
        }

        $instancia->update([
            'estado' => $novoEstado,
            'conectada_em' => $novoEstado === 'conectada' ? now() : null,
            'numero_whatsapp' => $novoEstado === 'conectada'
                ? ($instancia->numero_whatsapp ?? $this->extrairNumero($data))
                : $instancia->numero_whatsapp,
        ]);

        Log::info("Estado WhatsApp actualizado", [
            'instancia' => $instancia->evolution_instance_name,
            'de' => $estadoAnterior,
            'para' => $novoEstado,
        ]);

        if ($novoEstado === 'desconectada' && $estadoAnterior === 'conectada') {
            $this->notificarDesconectado($instancia);
        }
    }

    private function processarQrcodeUpdated(
        InstanciaWhatsApp $instancia,
        array $data
    ): void {
        if ($instancia->estado !== 'aguarda_qr') {
            $instancia->update(['estado' => 'aguarda_qr']);
        }
    }

    private function processarDisconnected(
        InstanciaWhatsApp $instancia,
        array $data
    ): void {
        $estadoAnterior = $instancia->estado;

        if ($instancia->estado === 'desconectada') {
            return;
        }

        $instancia->update([
            'estado' => 'desconectada',
            'conectada_em' => null,
        ]);

        Log::info("WhatsApp desconectado via evento", [
            'instancia' => $instancia->evolution_instance_name,
        ]);

        if ($estadoAnterior === 'conectada') {
            $this->notificarDesconectado($instancia);
        }
    }

    private function notificarDesconectado(InstanciaWhatsApp $instancia): void
    {
        $tenant = $instancia->tenant;

        if (!$tenant) {
            return;
        }

        $dono = $tenant->users()->first();

        if (!$dono) {
            return;
        }

        try {
            $dono->notify(new WhatsAppDesconectadoNotification(
                $tenant,
                $instancia,
                config('app.url') . '/painel/whatsapp'
            ));
        } catch (\Exception $e) {
            Log::error("Erro ao enviar notificação de desconexão", [
                'instancia' => $instancia->evolution_instance_name,
                'erro' => $e->getMessage(),
            ]);
        }
    }

    private function extrairNumero(array $data): ?string
    {
        return $data['data']['user'] ?? null;
    }
}
