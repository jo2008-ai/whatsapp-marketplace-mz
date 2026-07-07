<?php

namespace App\Services;

use App\Models\InstanciaWhatsApp;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WahaService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.waha.key', '');
    }

    private function resolveUrl(int $tenantId): string
    {
        $instancia = InstanciaWhatsApp::where('tenant_id', $tenantId)->first();

        if ($instancia?->waha_url) {
            return rtrim($instancia->waha_url, '/');
        }

        return rtrim(config('services.waha.url', 'http://localhost:3000'), '/');
    }

    /** @return array{sucesso: bool, dados?: mixed, erro?: string} */
    public function criarInstancia(int $tenantId, ?string $wahaUrl = null): array
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$baseUrl}/api/sessions", [
                    'name' => $nome,
                    'config' => [
                        'webhooks' => [
                            [
                                'url' => config('app.url')."/api/waha/webhook/{$tenantId}",
                                'events' => ['message', 'session.status'],
                            ],
                        ],
                    ],
                ]);

            if ($response->successful()) {
                Log::info('Instancia WAHA criada', ['tenant_id' => $tenantId, 'session' => $nome]);

                return ['sucesso' => true, 'dados' => $response->json()];
            }

            Log::warning('Falha ao criar instancia WAHA', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['sucesso' => false, 'erro' => "HTTP {$response->status()}"];
        } catch (\Exception $e) {
            Log::error('Excecao ao criar instancia WAHA', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    public function apagarInstancia(int $tenantId, ?string $wahaUrl = null): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->delete("{$baseUrl}/api/sessions/{$nome}");

            if ($response->successful() || $response->status() === 404) {
                Log::info('Instancia WAHA apagada', ['tenant_id' => $tenantId]);

                return true;
            }

            Log::warning('Falha ao apagar instancia WAHA', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Excecao ao apagar instancia WAHA', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function obterQrCode(int $tenantId, ?string $wahaUrl = null): ?string
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$baseUrl}/api/{$nome}/auth/qr");

            if ($response->successful()) {
                $contentType = $response->header('Content-Type') ?? '';

                if (str_contains($contentType, 'image/')) {
                    return base64_encode($response->body());
                }

                $data = $response->json();

                return $data['base64'] ?? $data['data'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Excecao ao obter QR code WAHA', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function obterEstado(int $tenantId, ?string $wahaUrl = null): string
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$baseUrl}/api/sessions/{$nome}");

            if ($response->successful()) {
                $data = $response->json();

                return $data['status'] ?? 'unknown';
            }

            if ($response->status() === 404) {
                return 'NOT_FOUND';
            }

            return 'ERROR';
        } catch (\Exception $e) {
            Log::error('Excecao ao obter estado WAHA', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return 'ERROR';
        }
    }

    public function ligar(int $tenantId, ?string $wahaUrl = null): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post("{$baseUrl}/api/sessions/{$nome}/start");

            if ($response->successful()) {
                Log::info('Sessao WAHA iniciada', ['tenant_id' => $tenantId]);

                return true;
            }

            Log::warning('Falha ao iniciar sessao WAHA', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Excecao ao iniciar sessao WAHA', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function desligar(int $tenantId, ?string $wahaUrl = null): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$baseUrl}/api/sessions/{$nome}/stop");

            if ($response->successful() || $response->status() === 404) {
                Log::info('Sessao WAHA parada', ['tenant_id' => $tenantId]);

                return true;
            }

            return false;
        } catch (\Exception $e) {
            Log::error('Excecao ao parar sessao WAHA', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function enviarMensagem(int $tenantId, string $numero, string $texto, ?string $wahaUrl = null): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $chatId = $this->normalizarNumero($numero);
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$baseUrl}/api/sendText", [
                    'session' => $nome,
                    'chatId' => $chatId,
                    'text' => $texto,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Falha ao enviar mensagem WAHA', [
                'tenant_id' => $tenantId,
                'numero' => $numero,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Excecao ao enviar mensagem WAHA', [
                'tenant_id' => $tenantId,
                'numero' => $numero,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<int, mixed> */
    public function listarInstancias(?string $wahaUrl = null): array
    {
        $baseUrl = $wahaUrl ? rtrim($wahaUrl, '/') : rtrim(config('services.waha.url', 'http://localhost:3000'), '/');

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$baseUrl}/api/sessions");

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Excecao ao listar instancias WAHA', [
                'erro' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function nomeInstancia(int $tenantId): string
    {
        return "loja-{$tenantId}";
    }

    /** @return array{X-Api-Key: string} */
    private function headers(): array
    {
        return ['X-Api-Key' => $this->apiKey];
    }

    private function normalizarNumero(string $numero): string
    {
        $limpo = preg_replace('/[^0-9]/', '', $numero) ?? '';

        if (str_ends_with($limpo, '@c.us')) {
            return $limpo;
        }

        return $limpo.'@c.us';
    }
}
