<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Services\WahaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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
            $this->wahaService->criarInstancia($tenant->id, $instancia->waha_url);
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

            $this->wahaService->criarInstancia($tenant->id, $instancia->waha_url);
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

        $wahaUrl = config('services.waha.url');

        Log::info('QR: pedido recebido', [
            'tenant_id' => $tenant->id,
            'instancia_id' => $instancia->id,
            'waha_url' => $wahaUrl,
            'waha_session' => $instancia->waha_session,
        ]);

        try {
            $estado = $this->wahaService->obterEstado($tenant->id, $wahaUrl);

            Log::info('QR: estado obtido', ['estado' => $estado, 'tenant_id' => $tenant->id]);

            if ($estado === 'NOT_FOUND' || $estado === 'ERROR') {
                Log::info('QR: sessao nao encontrada, a criar...', ['tenant_id' => $tenant->id]);
                $criar = $this->wahaService->criarInstancia($tenant->id, $wahaUrl);
                Log::info('QR: criar resultado', ['tenant_id' => $tenant->id, 'resultado' => $criar]);
                $ligar = $this->wahaService->ligar($tenant->id, $wahaUrl);
                Log::info('QR: ligar resultado', ['tenant_id' => $tenant->id, 'resultado' => $ligar]);

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

            if ($estado === 'STOPPED') {
                $ligar = $this->wahaService->ligar($tenant->id, $wahaUrl);
                Log::info('QR: sessao parada, a reiniciar', ['tenant_id' => $tenant->id, 'resultado' => $ligar]);

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                    'mensagem' => 'Sessao a reiniciar...',
                ]);
            }

            if ($estado === 'SCAN_QR_CODE' || $estado === 'STARTING') {
                $qrBase64 = $this->wahaService->obterQrCode($tenant->id, $wahaUrl);

                Log::info('QR: qr obtido', ['tenant_id' => $tenant->id, 'tem_qr' => $qrBase64 !== null]);

                if ($qrBase64) {
                    return response()->json([
                        'estado' => 'aguarda_qr',
                        'qr' => $qrBase64,
                    ]);
                }

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                    'mensagem' => 'A aguardar QR code...',
                ]);
            }

            return response()->json([
                'estado' => 'aguarda_qr',
                'qr' => null,
                'mensagem' => 'Sessao a iniciar...',
            ]);
        } catch (\Exception $e) {
            Log::error('QR: erro ao contactar WAHA', [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
                'waha_url' => $wahaUrl,
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

        $wahaUrl = config('services.waha.url');

        try {
            $estado = $this->wahaService->obterEstado($tenant->id, $wahaUrl);

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
