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
        $tenantId = $tenant->id;
        $wahaUrls = config('services.waha.urls', []);
        return view('painel.whatsapp.index', compact('instancias', 'tenant', 'tenantId', 'wahaUrls'));
    }

    public function conectar(Request $request)
    {
        $tenant = $request->user()->tenant;

        $instancia = $tenant->instancias()->first();

        if ($instancia) {
            if (!$instancia->waha_url) {
                $wahaUrl = $request->input('waha_url') ?: $this->getWahaUrl($tenant->id) ?? config('services.waha.url');
                $instancia->update(['waha_url' => $wahaUrl]);
            }
            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao WhatsApp ja existe.');
        }

        $wahaUrl = $request->input('waha_url') ?: $this->getWahaUrl($tenant->id);

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

        try {
            $response = Http::withHeaders([
                'X-Api-Key' => $this->getWahaKey(),
            ])->timeout(10)->get("{$wahaUrl}/api/{$session}/auth/qr");

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

            return response()->json(['erro' => 'Falha ao obter QR'], 500);
        } catch (\Exception $e) {
            return response()->json(['erro' => 'Servico indisponivel'], 503);
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
            ])->timeout(10)->get("{$wahaUrl}/api/{$session}");

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
