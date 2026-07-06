<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    private WahaService $wahaService;

    public function __construct(WahaService $wahaService)
    {
        $this->wahaService = $wahaService;
    }

    /** @return View */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }
        $instancias = $tenant->instancias()->get();

        return view('painel.whatsapp.index', compact('instancias', 'tenant'));
    }

    /** @return RedirectResponse|JsonResponse */
    public function conectar(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }

        $instancia = $tenant->instancias()->first();

        if ($instancia) {
            $this->wahaService->ligar($tenant->id, $instancia->waha_url);

            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao WhatsApp ja existe.');
        }

        try {
            $instancia = InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => 'default',
                'waha_session' => $this->wahaService->nomeInstancia($tenant->id),
                'waha_url' => config('services.waha.url'),
                'estado' => 'aguarda_qr',
            ]);

            $this->wahaService->ligar($tenant->id, $instancia->waha_url);

            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao criada! Escaneia o QR code.');
        } catch (\Exception $e) {
            \Log::error('Conectar: erro ao criar instancia', [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);

            return back()->with('error', 'Servico WhatsApp indisponivel. Tenta mais tarde.');
        }
    }

    /** @return JsonResponse */
    public function qr(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }
        $instanciaId = $request->input('instancia');
        $instancia = $tenant->instancias()->find($instanciaId);

        if (! $instancia) {
            return response()->json(['erro' => 'Instancia nao encontrada'], 404);
        }

        try {
            $estado = $this->wahaService->obterEstado($tenant->id, $instancia->waha_url);

            if ($estado === 'NOT_FOUND' || $estado === 'ERROR') {
                $this->wahaService->ligar($tenant->id, $instancia->waha_url);

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                    'mensagem' => 'Sessao a iniciar...',
                ]);
            }

            if ($estado === 'WORKING') {
                return response()->json([
                    'estado' => 'conectada',
                    'qr' => null,
                ]);
            }

            if ($estado !== 'STARTING' && $estado !== 'SCAN_QR_CODE') {
                $this->wahaService->ligar($tenant->id, $instancia->waha_url);

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                    'mensagem' => 'Sessao a iniciar...',
                ]);
            }

            $qrBase64 = $this->wahaService->obterQrCode($tenant->id, $instancia->waha_url);

            if ($qrBase64) {
                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => $qrBase64,
                ]);
            }

            return response()->json([
                'estado' => 'aguarda_qr',
                'qr' => null,
            ]);
        } catch (\Exception $e) {
            \Log::error('QR: erro ao contactar WAHA', [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'erro' => 'Servico indisponivel',
                'detalhe' => $e->getMessage(),
            ], 503);
        }
    }

    /** @return JsonResponse */
    public function estado(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (! $tenant) {
            abort(401);
        }
        $instancia = $tenant->instancias()->first();

        if (! $instancia) {
            return response()->json(['estado' => 'erro', 'error' => 'Sem instancia']);
        }

        try {
            $estado = $this->wahaService->obterEstado($tenant->id, $instancia->waha_url);

            if ($estado === 'WORKING') {
                return response()->json(['estado' => 'conectada', 'state' => $estado]);
            }

            if ($estado === 'NOT_FOUND') {
                return response()->json(['estado' => 'erro', 'error' => 'Sessao nao encontrada no WAHA']);
            }

            return response()->json(['estado' => 'desconectada', 'state' => $estado]);
        } catch (\Exception $e) {
            return response()->json(['estado' => 'erro', 'error' => $e->getMessage()]);
        }
    }
}
