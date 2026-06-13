<?php

namespace App\Http\Controllers;

use App\Http\Requests\BotWebhookRequest;
use App\Models\LogBot;
use App\Models\Tenant;
use App\Services\BotService;
use Illuminate\Http\JsonResponse;

class BotController extends Controller
{
    public function __construct(private BotService $botService)
    {
    }

    public function processar(BotWebhookRequest $request): JsonResponse
    {
        $instanceName = $request->input('instance_name');

        $tenant = Tenant::where('instancia_whatsapp', $instanceName)->first();

        if (!$tenant || !$tenant->activo) {
            return response()->json([
                'resposta' => 'Serviço temporariamente indisponível.',
                'enviar' => true,
            ]);
        }

        if ($request->boolean('is_grupo')) {
            return response()->json(['resposta' => null, 'enviar' => false]);
        }

        $numero = $request->input('numero');
        $mensagem = $request->input('mensagem');
        $nome = $request->input('nome', '');

        LogBot::create([
            'tenant_id' => $tenant->id,
            'numero_whatsapp' => $numero,
            'direcao' => 'entrada',
            'mensagem' => $mensagem,
        ]);

        $resposta = $this->botService->responder(
            $tenant,
            $numero,
            $mensagem,
            $nome
        );

        if (is_array($resposta)) {
            $texto = $resposta['texto'] ?? '';
            $imagens = $resposta['imagens'] ?? [];

            LogBot::create([
                'tenant_id' => $tenant->id,
                'numero_whatsapp' => $numero,
                'direcao' => 'saida',
                'mensagem' => $texto,
            ]);

            return response()->json([
                'resposta' => $texto,
                'imagens' => $imagens,
                'enviar' => true,
            ]);
        }

        LogBot::create([
            'tenant_id' => $tenant->id,
            'numero_whatsapp' => $numero,
            'direcao' => 'saida',
            'mensagem' => $resposta,
        ]);

        return response()->json([
            'resposta' => $resposta,
            'enviar' => true,
        ]);
    }
}
