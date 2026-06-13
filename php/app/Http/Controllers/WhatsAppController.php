<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
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
            return redirect("/painel/whatsapp?instancia={$instancia->id}")
                ->with('success', 'Sessao WhatsApp ja existe.');
        }

        try {
            $instancia = InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => 'default',
                'waha_session' => 'default',
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

        try {
            $response = Http::timeout(10)->get(
                config('services.python.url') . "/qr"
            );

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['estado']) && $data['estado'] === 'conectada') {
                    $instancia->update([
                        'estado' => 'conectada',
                        'conectada_em' => now(),
                        'numero_whatsapp' => $data['numero'] ?? null,
                    ]);
                }
                return response()->json($data);
            }

            return response()->json(['erro' => 'Falha ao obter QR'], 500);
        } catch (\Exception $e) {
            return response()->json(['erro' => 'Servico indisponivel'], 503);
        }
    }
}
