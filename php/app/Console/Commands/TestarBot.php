<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\BotService;
use Illuminate\Console\Command;

class TestarBot extends Command
{
    protected $signature = 'bot:testar
        {tenant_id : ID do tenant/loja}
        {numero : Numero do cliente (ex: 841234567)}
        {--mensagem= : Mensagem a enviar (opcional, modo interativo se omitido)}
        {--nome= : Nome do cliente}
        {--limpar : Limpar sessao do bot antes de comecar}';

    protected $description = 'Testar o bot enviando mensagens diretamente';

    private BotService $botService;

    public function __construct(BotService $botService)
    {
        parent::__construct();
        $this->botService = $botService;
    }

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        $numero = $this->argument('numero');
        $nome = $this->option('nome') ?? '';
        $mensagemFixa = $this->option('mensagem');

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant #{$tenantId} nao encontrado.");

            return 1;
        }

        if (! $tenant->activo) {
            $this->error("Tenant #{$tenantId} esta inativo.");

            return 1;
        }

        $this->info("Testando bot da loja: {$tenant->nome_loja}");
        $this->info("Numero do cliente: {$numero}");
        $this->newLine();

        if ($this->option('limpar')) {
            $this->limparSessao($tenantId, $numero);
        }

        if ($mensagemFixa) {
            $this->enviarEExibir($tenant, $numero, $mensagemFixa, $nome);

            return 0;
        }

        $this->info('Modo interativo. Escreva sua mensagem ou "sair" para terminar.');
        $this->newLine();

        while (true) {
            $mensagem = $this->ask('Mensagem');

            if (in_array(strtolower(trim($mensagem ?? '')), ['sair', 'exit', 'quit', 'q'])) {
                $this->info('Ate logo!');

                return 0;
            }

            if (empty(trim($mensagem ?? ''))) {
                $this->warn('Mensagem vazia. Tenta novamente.');

                continue;
            }

            $this->enviarEExibir($tenant, $numero, $mensagem, $nome);
            $this->newLine();
        }
    }

    private function enviarEExibir(Tenant $tenant, string $numero, string $mensagem, string $nome): void
    {
        $this->line("<info>👤 Cliente:</info> {$mensagem}");

        $resposta = $this->botService->responder($tenant, $numero, $mensagem, $nome);

        if (is_array($resposta)) {
            $this->line("<comment>🤖 Bot:</comment> {$resposta['texto']}");

            if (! empty($resposta['imagens'])) {
                $this->line('<info>📷 Imagens:</info> '.implode(', ', $resposta['imagens']));
            }
        } else {
            $this->line("<comment>🤖 Bot:</comment> {$resposta}");
        }
    }

    private function limparSessao(int $tenantId, string $numero): void
    {
        $deleted = \App\Models\SessaoBot::where('tenant_id', $tenantId)
            ->where('numero_whatsapp', $numero)
            ->delete();

        if ($deleted) {
            $this->info('Sessao do bot limpa com sucesso.');
        } else {
            $this->warn('Nenhuma sessao encontrada para limpar.');
        }

        $this->newLine();
    }
}
