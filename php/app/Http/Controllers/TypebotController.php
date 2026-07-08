<?php

namespace App\Http\Controllers;

use App\Models\SessaoBot;
use App\Models\Tenant;
use App\Services\BotService;
use App\Services\TypebotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TypebotController extends Controller
{
    public function __construct(
        private TypebotService $typebotService,
        private BotService $botService
    ) {
    }

    public function webhook(Request $request, int $tenantId): JsonResponse
    {
        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->activo) {
            return response()->json(['error' => 'tenant inactive'], 400);
        }

        if (!$tenant->usar_typebot) {
            return response()->json(['error' => 'typebot not enabled'], 400);
        }

        $data = $request->all();
        $numero = $data['numero'] ?? '';
        $mensagem = $data['mensagem'] ?? '';
        $nome = $data['nome'] ?? '';

        if (!$numero || !$mensagem) {
            return response()->json(['error' => 'missing fields'], 400);
        }

        $sessao = SessaoBot::obter($tenant->id, $numero);

        if ($sessao->estado === 'transferido_vendedor') {
            $resposta = $this->botService->responder($tenant, $numero, $mensagem, $nome);
            return response()->json([
                'resposta' => is_array($resposta) ? $resposta['texto'] ?? '' : $resposta,
                'imagens' => is_array($resposta) ? $resposta['imagens'] ?? [] : [],
                'enviar' => true,
            ]);
        }

        $typebotData = $sessao->dados['typebot'] ?? null;

        if (!$typebotData || !isset($typebotData['session_id'])) {
            $resultado = $this->typebotService->iniciarSessao($tenant, $numero, $mensagem, $nome);

            if (!$resultado || empty($resultado['session_id'])) {
                $resposta = $this->botService->responder($tenant, $numero, $mensagem, $nome);
                return response()->json([
                    'resposta' => is_array($resposta) ? $resposta['texto'] ?? '' : $resposta,
                    'imagens' => is_array($resposta) ? $resposta['imagens'] ?? [] : [],
                    'enviar' => true,
                ]);
            }

            $sessao->atualizarEstado('typebot', [
                'typebot' => [
                    'session_id' => $resultado['session_id'],
                ],
            ]);

            $mensagens = $this->typebotService->processarRespostas($resultado['messages']);
            $texto = $this->formatarMensagens($mensagens);

            if ($texto) {
                return response()->json([
                    'resposta' => $texto,
                    'enviar' => true,
                ]);
            }

            return response()->json([
                'resposta' => 'A processar...',
                'enviar' => true,
            ]);
        }

        $resultado = $this->typebotService->enviarMensagem(
            $tenant,
            $typebotData['session_id'],
            $mensagem
        );

        if (!$resultado) {
            $sessao->atualizarEstado('inicio');
            $resposta = $this->botService->responder($tenant, $numero, $mensagem, $nome);
            return response()->json([
                'resposta' => is_array($resposta) ? $resposta['texto'] ?? '' : $resposta,
                'imagens' => is_array($resposta) ? $resposta['imagens'] ?? [] : [],
                'enviar' => true,
            ]);
        }

        $mensagens = $this->typebotService->processarRespostas($resultado['messages']);
        $texto = $this->formatarMensagens($mensagens);

        if ($texto) {
            return response()->json([
                'resposta' => $texto,
                'enviar' => true,
            ]);
        }

        return response()->json([
            'resposta' => null,
            'enviar' => false,
        ]);
    }

    public function config(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (!$tenant) {
            abort(401);
        }

        return response()->json([
            'usar_typebot' => $tenant->usar_typebot,
            'typebot_bot_id' => $tenant->typebot_bot_id,
            'typebot_api_url' => $tenant->typebot_api_url,
        ]);
    }

    public function guardarConfig(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (!$tenant) {
            abort(401);
        }

        $validated = $request->validate([
            'usar_typebot' => 'boolean',
            'typebot_bot_id' => 'nullable|string|max:255',
            'typebot_api_url' => 'nullable|url|max:500',
        ]);

        $tenant->update([
            'usar_typebot' => $validated['usar_typebot'] ?? false,
            'typebot_bot_id' => $validated['typebot_bot_id'] ?? null,
            'typebot_api_url' => $validated['typebot_api_url'] ?? null,
        ]);

        return response()->json(['success' => true]);
    }

    public function listarBots(Request $request): JsonResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(401);
        }
        $tenant = $user->tenant;
        if (!$tenant) {
            abort(401);
        }

        $bots = $this->typebotService->listarBots($tenant);

        return response()->json(['bots' => $bots]);
    }

    /**
     * @param array<int, array{tipo: string, conteudo: string, botoes?: array<int, string>}> $mensagens
     */
    private function formatarMensagens(array $mensagens): string
    {
        $texto = '';

        foreach ($mensagens as $msg) {
            if ($msg['tipo'] === 'texto') {
                $texto .= ($texto ? "\n\n" : '') . $msg['conteudo'];
            }

            if ($msg['tipo'] === 'botoes') {
                $texto .= ($texto ? "\n\n" : '') . $msg['conteudo'];
                foreach ($msg['botoes'] ?? [] as $i => $botao) {
                    $num = $i + 1;
                    $texto .= "\n{$num}️⃣ {$botao}";
                }
            }
        }

        return $texto;
    }
}
