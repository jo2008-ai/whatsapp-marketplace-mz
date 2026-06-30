<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    private function getWahaUrl(int $tenantId): ?string
    {
        return config("services.waha.urls.{$tenantId}");
    }

    private function getWahaKey(): string
    {
        return config('services.waha.key', '');
    }

    private function resolveWahaUrl(InstanciaWhatsApp $instancia): ?string
    {
        if ($instancia->waha_url) {
            return $instancia->waha_url;
        }

        return $this->getWahaUrl($instancia->tenant_id)
            ?? config('services.waha.url')
            ?? env('WAHA_URL_1');
    }

    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        $instancias = $tenant->instancias()->get();
        return view('painel.whatsapp.index', compact('instancias', 'tenant'));
    }

    public function conectar(Request $request)
    {
        $tenant = $request->user()->tenant;

        $instancia = $tenant->instancias()->first();

        if ($instancia) {
            if (!$instancia->waha_url) {
                $instancia->update(['waha_url' => $this->resolveWahaUrl($instancia)]);
            }
            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao WhatsApp ja existe.');
        }

        $wahaUrl = $this->getWahaUrl($tenant->id)
            ?? config('services.waha.url')
            ?? env('WAHA_URL_1');

        try {
            $instancia = InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => 'default',
                'waha_session' => 'default',
                'waha_url' => $wahaUrl,
                'estado' => 'aguarda_qr',
            ]);

            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao criada! Escaneia o QR code.');
        } catch (\Exception $e) {
            return back()->with('error', 'Servico WhatsApp indisponivel. Tenta mais tarde.');
        }
    }

    public function qr(Request $request)
    {
        $tenant = $request->user()->tenant;
        $instanciaId = $request->input('instancia');
        $instancia = $tenant->instancias()->find($instanciaId);

        if (!$instancia) {
            return response()->json(['erro' => 'Instancia nao encontrada'], 404);
        }

        $wahaUrl = $this->resolveWahaUrl($instancia);
        if (!$wahaUrl) {
            return response()->json(['erro' => 'WAHA nao configurado para este tenant'], 500);
        }

        $session = $instancia->waha_session ?: 'default';
        $wahaKey = $this->getWahaKey();
        $headers = ['X-Api-Key' => $wahaKey];

        try {
            \Log::info("QR: a contactar WAHA", [
                'wahaUrl' => $wahaUrl,
                'session' => $session,
                'instancia_id' => $instancia->id,
            ]);

            $statusResp = null;
            for ($attempt = 1; $attempt <= 3; $attempt++) {
                $statusResp = Http::withHeaders($headers)
                    ->timeout(30)
                    ->get("{$wahaUrl}/api/{$session}");

                \Log::info("QR: status response", [
                    'attempt' => $attempt,
                    'status' => $statusResp->status(),
                    'body' => $statusResp->body(),
                ]);

                if ($statusResp->status() === 401) {
                    return response()->json([
                        'erro' => 'Chave de API invalida. Verifica WAHA_SECRET no Render.',
                        'waha_url' => $wahaUrl,
                    ], 401);
                }

                if ($statusResp->successful()) {
                    break;
                }

                if ($attempt < 3) {
                    sleep(3);
                }
            }

            if ($statusResp && $statusResp->successful()) {
                $statusData = $statusResp->json();
                $currentState = $statusData['status'] ?? 'unknown';

                if ($currentState !== 'STARTING' && $currentState !== 'SCAN_QR_CODE') {
                    \Log::info("QR: sessao nao esta pronta, a iniciar", ['currentState' => $currentState]);

                    $startResp = Http::withHeaders($headers)
                        ->timeout(30)
                        ->post("{$wahaUrl}/api/{$session}/start");

                    \Log::info("QR: start response", ['status' => $startResp->status()]);

                    return response()->json([
                        'estado' => 'aguarda_qr',
                        'qr' => null,
                        'mensagem' => 'Sessao a iniciar...',
                    ]);
                }
            }

            $response = Http::withHeaders($headers)
                ->timeout(30)
                ->get("{$wahaUrl}/api/{$session}/auth/qr");

            \Log::info("QR: qr response", [
                'status' => $response->status(),
                'has_base64' => isset($response->json()['base64']),
            ]);

            if ($response->successful()) {
                $data = $response->json();

                if (isset($data['base64']) && $data['base64']) {
                    return response()->json([
                        'estado' => 'aguarda_qr',
                        'qr' => $data['base64'],
                    ]);
                }

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                ]);
            }

            return response()->json(['erro' => 'Falha ao obter QR', 'waha_url' => $wahaUrl], 500);
        } catch (\Exception $e) {
            \Log::error("QR: erro ao contactar WAHA", [
                'wahaUrl' => $wahaUrl,
                'session' => $session,
                'erro' => $e->getMessage(),
            ]);
            return response()->json([
                'erro' => 'Servico indisponivel',
                'waha_url' => $wahaUrl,
                'detalhe' => $e->getMessage(),
            ], 503);
        }
    }

    public function estado(Request $request)
    {
        $tenant = $request->user()->tenant;
        $instancia = $tenant->instancias()->first();

        if (!$instancia) {
            return response()->json(['estado' => 'erro', 'error' => 'Sem instancia']);
        }

        $wahaUrl = $this->resolveWahaUrl($instancia);
        if (!$wahaUrl) {
            return response()->json(['estado' => 'erro', 'error' => 'WAHA nao configurado']);
        }

        $session = $instancia->waha_session ?: 'default';

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->getWahaKey(),
            ])->timeout(30)->get("{$wahaUrl}/api/{$session}");

            if ($response->successful()) {
                $data = $response->json();
                $state = $data['status'] ?? 'unknown';
                return response()->json([
                    'estado' => $state === 'WORKING' ? 'conectada' : 'desconectada',
                    'state' => $state,
                ]);
            }

            return response()->json(['estado' => 'erro', 'error' => 'HTTP ' . $response->status()]);
        } catch (\Exception $e) {
            return response()->json(['estado' => 'erro', 'error' => $e->getMessage()]);
        }
    }
}
