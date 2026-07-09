<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use App\Services\EvolutionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class WhatsAppController extends Controller
{
    private EvolutionService $evolutionService;

    public function __construct(EvolutionService $evolutionService)
    {
        $this->evolutionService = $evolutionService;
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
            $this->evolutionService->criarInstancia($tenant->id, $instancia->waha_url);

            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao WhatsApp ja existe.');
        }

        try {
            $instancia = InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => 'default',
                'waha_session' => $this->evolutionService->nomeInstancia($tenant->id),
                'waha_url' => config('services.evolution.url'),
                'estado' => 'aguarda_qr',
            ]);

            $this->evolutionService->criarInstancia($tenant->id, $instancia->waha_url);

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

        $evolutionUrl = config('services.evolution.url');

        try {
            $estado = $this->evolutionService->obterEstado($tenant->id, $evolutionUrl);

            if ($estado === 'NOT_FOUND' || $estado === 'ERROR') {
                $this->evolutionService->criarInstancia($tenant->id, $evolutionUrl);

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                    'mensagem' => 'Sessao a iniciar...',
                ]);
            }

            if ($estado === 'open') {
                return response()->json([
                    'estado' => 'conectada',
                    'qr' => null,
                ]);
            }

            if ($estado === 'close') {
                $this->evolutionService->criarInstancia($tenant->id, $evolutionUrl);

                return response()->json([
                    'estado' => 'aguarda_qr',
                    'qr' => null,
                    'mensagem' => 'Sessao a reiniciar...',
                ]);
            }

            $qrBase64 = $this->evolutionService->obterQrCode($tenant->id, $evolutionUrl);

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
        } catch (\Exception $e) {
            Log::error('QR: erro ao contactar Evolution API', [
                'tenant_id' => $tenant->id,
                'erro' => $e->getMessage(),
            ]);

            return response()->json([
                'erro' => 'Servico indisponivel',
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

        $evolutionUrl = config('services.evolution.url');

        try {
            $estado = $this->evolutionService->obterEstado($tenant->id, $evolutionUrl);

            if ($estado === 'open') {
                return response()->json(['estado' => 'conectada', 'state' => $estado]);
            }

            if ($estado === 'NOT_FOUND') {
                return response()->json(['estado' => 'erro', 'error' => 'Sessao nao encontrada na Evolution API']);
            }

            return response()->json(['estado' => 'desconectada', 'state' => $estado]);
        } catch (\Exception $e) {
            return response()->json(['estado' => 'erro', 'error' => $e->getMessage()]);
        }
    }
}
