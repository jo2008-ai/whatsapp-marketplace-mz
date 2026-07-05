<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.waha.url', ''), '/');
        $this->apiKey = config('services.waha.key', '');
    }

    public function criarInstancia(int $tenantId): array
    {
        $nome = $this->nomeInstancia($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/api/sessions", [
                    'name' => $nome,
                    'config' => [
                        'webhooks' => [
                            [
                                'url' => config('app.url') . "/api/waha/webhook/{$tenantId}",
                                'events' => ['message', 'session.status'],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::info("Instancia WAHA criada", ['tenant_id' => $tenantId, 'session' => $nome]);
                return ['sucesso' => true, 'dados' => $response->json()];
            }

            Log::warning("Falha ao criar instancia WAHA", [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['sucesso' => false, 'erro' => "HTTP {$response->status()}"];
        } catch (\Exception $e) {
            Log::error("Excecao ao criar instancia WAHA", [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    public function apagarInstancia(int $tenantId): bool
    {
        $nome = $this->nomeInstancia($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->delete("{$this->baseUrl}/api/sessions/{$nome}");

            if ($response->successful() || $response->status() === 404) {
                Log::info("Instancia WAHA apagada", ['tenant_id' => $tenantId]);
                return true;
            }

            Log::warning("Falha ao apagar instancia WAHA", [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Excecao ao apagar instancia WAHA", [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function obterQrCode(int $tenantId): ?string
    {
        $nome = $this->nomeInstancia($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$this->baseUrl}/api/{$nome}/auth/qr");

            if ($response->successful()) {
                $data = $response->json();
                return $data['base64'] ?? $data['data'] ?? null;
            }

            Log::warning("Falha ao obter QR code WAHA", [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error("Excecao ao obter QR code WAHA", [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function obterEstado(int $tenantId): string
    {
        $nome = $this->nomeInstancia($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->baseUrl}/api/sessions/{$nome}");

            if ($response->successful()) {
                $data = $response->json();
                return $data['status'] ?? 'unknown';
            }

            if ($response->status() === 404) {
                return 'NOT_FOUND';
            }

            return 'ERROR';
        } catch (\Exception $e) {
            Log::error("Excecao ao obter estado WAHA", [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return 'ERROR';
        }
    }

    public function ligar(int $tenantId): bool
    {
        $nome = $this->nomeInstancia($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post("{$this->baseUrl}/api/sessions/{$nome}/start");

            if ($response->successful()) {
                Log::info("Sessao WAHA iniciada", ['tenant_id' => $tenantId]);
                return true;
            }

            Log::warning("Falha ao iniciar sessao WAHA", [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Excecao ao iniciar sessao WAHA", [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function desligar(int $tenantId): bool
    {
        $nome = $this->nomeInstancia($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/api/sessions/{$nome}/stop");

            if ($response->successful() || $response->status() === 404) {
                Log::info("Sessao WAHA parada", ['tenant_id' => $tenantId]);
                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error("Excecao ao parar sessao WAHA", [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarMensagem(int $tenantId, string $numero, string $texto): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $chatId = $this->normalizarNumero($numero);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$this->baseUrl}/api/sendText", [
                    'session' => $nome,
                    'chatId' => $chatId,
                    'text' => $texto,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning("Falha ao enviar mensagem WAHA", [
                'tenant_id' => $tenantId,
                'numero' => $numero,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error("Excecao ao enviar mensagem WAHA", [
                'tenant_id' => $tenantId,
                'numero' => $numero,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function listarInstancias(): array
    {
        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$this->baseUrl}/api/sessions");

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error("Excecao ao listar instancias WAHA", [
                'erro' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function nomeInstancia(int $tenantId): string
    {
        return "loja-{$tenantId}";
    }

    private function headers(): array
    {
        return ['X-Api-Key' => $this->apiKey];
    }

    private function normalizarNumero(string $numero): string
    {
        $limpo = preg_replace('/[^0-9]/', '', $numero);

        if (str_ends_with($limpo, '@c.us')) {
            return $limpo;
        }

        return $limpo . '@c.us';
    }
}
