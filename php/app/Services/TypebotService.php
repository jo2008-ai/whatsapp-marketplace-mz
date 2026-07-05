<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TypebotService
{
    private string $apiUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->apiUrl = config('services.typebot.url', 'http://typebot-viewer:3000');
        $this->apiKey = config('services.typebot.key', 'typebot_secret_key_2026');
    }

    /** @return array{session_id: string, messages: array<int, mixed>}|null */
    public function iniciarSessao(Tenant $tenant, string $numero, string $mensagem, string $nome = ''): ?array
    {
        $botId = $tenant->typebot_bot_id;

        if (!$botId) {
            return null;
        }

        $apiUrl = $tenant->typebot_api_url ?: $this->apiUrl;

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$apiUrl}/api/v1/blobs/upload", [
                    'workspaceId' => $this->getWorkspaceId($tenant),
                ]);

            $startResponse = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$apiUrl}/api/v1/sessions", [
                    'botId' => $botId,
                    'startParams' => [
                        'isPreview' => false,
                        'prefilledInput' => [
                            'phoneNumber' => $numero,
                            'nome' => $nome,
                            'message' => $mensagem,
                        ],
                    ],
                ]);

            if ($startResponse->successful()) {
                $data = $startResponse->json();
                return [
                    'session_id' => $data['sessionId'] ?? null,
                    'messages' => $data['messages'] ?? [],
                ];
            }

            Log::error("Typebot API erro:", [
                'tenant_id' => $tenant->id,
                'status' => $startResponse->status(),
                'body' => $startResponse->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Typebot API exceção: " . $e->getMessage());
            return null;
        }
    }

    /** @return array{messages: array<int, mixed>}|null */
    public function enviarMensagem(Tenant $tenant, string $sessionId, string $mensagem): ?array
    {
        $apiUrl = $tenant->typebot_api_url ?: $this->apiUrl;

        try {
            $response = Http::timeout(15)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                    'Content-Type' => 'application/json',
                ])
                ->post("{$apiUrl}/api/v1/sessions/{$sessionId}/continue", [
                    'input' => [
                        'type' => 'text',
                        'content' => $mensagem,
                    ],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'messages' => $data['messages'] ?? [],
                ];
            }

            Log::error("Typebot continue erro:", [
                'tenant_id' => $tenant->id,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Typebot continue exceção: " . $e->getMessage());
            return null;
        }
    }

    /** @return array<int, mixed> */
    public function listarBots(Tenant $tenant): array
    {
        $apiUrl = $tenant->typebot_api_url ?: $this->apiUrl;

        try {
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => "Bearer {$this->apiKey}",
                ])
                ->get("{$apiUrl}/api/v1/bots");

            if ($response->successful()) {
                return $response->json('bots', []);
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Typebot listar bots erro: " . $e->getMessage());
            return [];
        }
    }

    /**
     * @param array<int, mixed> $messages
     * @return array<int, array{tipo: string, conteudo: string, botoes?: array<int, string>}>
     */
    public function processarRespostas(array $messages): array
    {
        $resultados = [];

        foreach ($messages as $message) {
            if (isset($message['type']) && $message['type'] === 'text') {
                $resultados[] = [
                    'tipo' => 'texto',
                    'conteudo' => $message['content'] ?? '',
                ];
            }

            if (isset($message['type']) && $message['type'] === 'image') {
                $resultados[] = [
                    'tipo' => 'imagem',
                    'conteudo' => $message['url'] ?? '',
                ];
            }

            if (isset($message['type']) && $message['type'] === 'buttons') {
                $botoes = [];
                foreach ($message['buttons'] ?? [] as $botao) {
                    $botoes[] = $botao['text'] ?? '';
                }
                $resultados[] = [
                    'tipo' => 'botoes',
                    'conteudo' => $message['text'] ?? '',
                    'botoes' => $botoes,
                ];
            }
        }

        return $resultados;
    }

    private function getWorkspaceId(Tenant $tenant): string
    {
        return "tenant_{$tenant->id}";
    }
}
