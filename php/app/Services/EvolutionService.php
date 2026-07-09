<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EvolutionService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = trim(config('services.evolution.key', ''));
    }

    private function resolveUrl(int $tenantId): string
    {
        $instancia = \App\Models\InstanciaWhatsApp::where('tenant_id', $tenantId)->first();

        if ($instancia?->waha_url) {
            return rtrim($instancia->waha_url, '/');
        }

        return rtrim(config('services.evolution.url', ''), '/');
    }

    /** @return array{sucesso: bool, dados?: mixed, erro?: string} */
    public function criarInstancia(int $tenantId, ?string $evolutionUrl = null): array
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $evolutionUrl ? rtrim($evolutionUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $payload = [
                'instanceName' => $nome,
                'qrcode' => true,
                'integration' => 'WHATSAPP-BAILEYS',
                'webhook' => [
                    'url' => config('services.evolution.webhook_base_url', config('app.url', ''))
                        ."/api/evolution/webhook/{$tenantId}",
                    'events' => ['MESSAGES_UPSERT', 'CONNECTION_UPDATE'],
                ],
            ];

            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->post("{$baseUrl}/instance/create", $payload);

            if ($response->successful()) {
                Log::info('Instancia Evolution criada', ['tenant_id' => $tenantId, 'session' => $nome]);

                return ['sucesso' => true, 'dados' => $response->json()];
            }

            if ($response->status() === 422) {
                Log::info('Sessao ja existe na Evolution, deletando e recriando', ['tenant_id' => $tenantId]);
                $this->apagarInstancia($tenantId, $evolutionUrl);

                $response = Http::withHeaders($this->headers())
                    ->timeout(30)
                    ->post("{$baseUrl}/instance/create", $payload);

                if ($response->successful()) {
                    Log::info('Instancia Evolution recriada', ['tenant_id' => $tenantId]);

                    return ['sucesso' => true, 'dados' => $response->json()];
                }
            }

            Log::warning('Falha ao criar instancia Evolution', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return ['sucesso' => false, 'erro' => "HTTP {$response->status()}"];
        } catch (\Exception $e) {
            Log::error('Excecao ao criar instancia Evolution', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return ['sucesso' => false, 'erro' => $e->getMessage()];
        }
    }

    public function apagarInstancia(int $tenantId, ?string $evolutionUrl = null): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $evolutionUrl ? rtrim($evolutionUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->delete("{$baseUrl}/instance/delete/{$nome}");

            if ($response->successful() || $response->status() === 404) {
                Log::info('Instancia Evolution apagada', ['tenant_id' => $tenantId]);

                return true;
            }

            Log::warning('Falha ao apagar instancia Evolution', [
                'tenant_id' => $tenantId,
                'status' => $response->status(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Excecao ao apagar instancia Evolution', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function obterQrCode(int $tenantId, ?string $evolutionUrl = null): ?string
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $evolutionUrl ? rtrim($evolutionUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$baseUrl}/instance/connect/{$nome}");

            if ($response->successful()) {
                $data = $response->json();

                return $data['base64'] ?? $data['qrcode']['base64'] ?? null;
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Excecao ao obter QR code Evolution', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function obterEstado(int $tenantId, ?string $evolutionUrl = null): string
    {
        $nome = $this->nomeInstancia($tenantId);
        $baseUrl = $evolutionUrl ? rtrim($evolutionUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(30)
                ->get("{$baseUrl}/instance/connectionState/{$nome}");

            if ($response->successful()) {
                $data = $response->json();

                return $data['instance']['state'] ?? 'unknown';
            }

            if ($response->status() === 404) {
                return 'NOT_FOUND';
            }

            return 'ERROR';
        } catch (\Exception $e) {
            Log::error('Excecao ao obter estado Evolution', [
                'tenant_id' => $tenantId,
                'erro' => $e->getMessage(),
            ]);

            return 'ERROR';
        }
    }

    public function enviarMensagem(int $tenantId, string $numero, string $texto, ?string $evolutionUrl = null): bool
    {
        $nome = $this->nomeInstancia($tenantId);
        $numeroLimpo = $this->normalizarNumero($numero);
        $baseUrl = $evolutionUrl ? rtrim($evolutionUrl, '/') : $this->resolveUrl($tenantId);

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->post("{$baseUrl}/message/sendText/{$nome}", [
                    'number' => $numeroLimpo,
                    'text' => $texto,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::warning('Falha ao enviar mensagem Evolution', [
                'tenant_id' => $tenantId,
                'numero' => $numero,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Excecao ao enviar mensagem Evolution', [
                'tenant_id' => $tenantId,
                'numero' => $numero,
                'erro' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /** @return array<int, mixed> */
    public function listarInstancias(?string $evolutionUrl = null): array
    {
        $baseUrl = $evolutionUrl ? rtrim($evolutionUrl, '/') : rtrim(config('services.evolution.url', ''), '/');

        try {
            $response = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("{$baseUrl}/instance/fetchInstances");

            if ($response->successful()) {
                return $response->json();
            }

            return [];
        } catch (\Exception $e) {
            Log::error('Excecao ao listar instancias Evolution', [
                'erro' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function nomeInstancia(int $tenantId): string
    {
        return "loja-{$tenantId}";
    }

    /** @return array{apikey: string, Content-Type: string} */
    private function headers(): array
    {
        return [
            'apikey' => $this->apiKey,
            'Content-Type' => 'application/json',
        ];
    }

    private function normalizarNumero(string $numero): string
    {
        return preg_replace('/[^0-9]/', '', $numero) ?? $numero;
    }
}
