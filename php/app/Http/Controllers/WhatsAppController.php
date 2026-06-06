<?php

namespace App\Http\Controllers;

use App\Models\InstanciaWhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

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

        if (!$tenant->podeAdicionarNumero()) {
            return back()->with('error', 'Limite de números atingido para o teu plano.');
        }

        $nomeInstancia = 'loja_' . $tenant->id . '_' . Str::random(6);

        try {
            $response = Http::timeout(15)->post(config('services.python.url') . '/conectar-instancia', [
                'instance_name' => $nomeInstancia,
                'tenant_id' => $tenant->id,
            ]);

            if (!$response->successful()) {
                return back()->with('error', 'Erro ao criar instância. Tenta novamente.');
            }

            $instancia = InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => $nomeInstancia,
                'evolution_instance_name' => $nomeInstancia,
                'estado' => 'aguarda_qr',
            ]);

            return redirect("/painel/whatsapp?instancia={$instancia->id}")->with('success', 'Instância criada! Escaneia o QR code.');
        } catch (\Exception $e) {
            return back()->with('error', 'Serviço WhatsApp indisponível. Tenta mais tarde.');
        }
    }

    public function qr(Request $request)
    {
        $tenant = $request->user()->tenant;
        $instanciaId = $request->input('instancia');

        $instancia = $tenant->instancias()->find($instanciaId);

        if (!$instancia) {
            return response()->json(['erro' => 'Instância não encontrada'], 404);
        }

        try {
            $response = Http::timeout(10)->get(
                config('services.python.url') . "/qr/{$instancia->evolution_instance_name}"
            );

            if ($response->successful()) {
                $data = $response->json();

                // Atualizar estado se conectou
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
            return response()->json(['erro' => 'Serviço indisponível'], 503);
        }
    }
}
