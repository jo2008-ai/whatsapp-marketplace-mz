<?php

namespace Tests\Unit;

use App\Models\Categoria;
use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\SessaoBot;
use App\Models\Tenant;
use App\Models\Vendedor;
use App\Services\BotService;
use App\Services\NotificacaoService;
use App\Services\TypebotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class BotServiceTest extends TestCase
{
    use RefreshDatabase;

    private BotService $bot;
    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $notificacaoMock = Mockery::mock(NotificacaoService::class);
        $notificacaoMock->shouldReceive('notificarVendedor')->andReturnNull();

        $typebotMock = Mockery::mock(TypebotService::class);
        $typebotMock->shouldReceive('processar')->andReturnNull();

        $this->bot = new BotService($notificacaoMock, $typebotMock);

        $this->tenant = Tenant::create([
            'nome_loja' => 'Teste Loja',
            'email_dono' => 'teste@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'activo' => true,
            'max_produtos' => 50,
            'max_numeros' => 1,
            'cor_primaria' => '#2563EB',
        ]);

        $cat = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
            'icone' => '🍎',
            'ordem' => 1,
        ]);

        $vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Vendedor Teste',
            'numero_whatsapp' => '+258840000000',
        ]);

        Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $cat->id,
            'vendedor_id' => $vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
            'disponivel' => true,
        ]);

        Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $cat->id,
            'vendedor_id' => $vendedor->id,
            'nome' => 'Maça',
            'preco' => 120,
            'stock' => 0,
            'disponivel' => true,
        ]);
    }

    private function getResposta(mixed $resposta): string
    {
        return is_array($resposta) ? ($resposta['resposta'] ?? json_encode($resposta)) : (string) $resposta;
    }

    public function test_saudacao_retorna_menu(): void
    {
        $resposta = $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $texto = $this->getResposta($resposta);
        $this->assertStringContainsString('Teste Loja', $texto);
        $this->assertStringContainsString('produtos', $texto);
    }

    public function test_loja_suspensa_retorna_indisponivel(): void
    {
        $this->tenant->update(['activo' => false]);
        $resposta = $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $texto = $this->getResposta($resposta);
        $this->assertStringContainsString('indisponível', $texto);
    }

    public function test_menu_opcao_1_mostra_categorias(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1');
        $texto = $this->getResposta($resposta);
        $this->assertStringContainsString('Frutas', $texto);
    }

    public function test_categoria_selecionada_mostra_produtos(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $this->bot->responder($this->tenant, '+258841111111', '1');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1');
        $texto = $this->getResposta($resposta);
        $this->assertStringContainsString('Banana', $texto);
    }

    public function test_produto_sem_stock_nao_permite_encomendar(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->bot->responder($this->tenant, '+258841111111', '2');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1');
        $texto = $this->getResposta($resposta);
        $this->assertStringContainsString('stock', mb_strtolower($texto));
    }

    public function test_encomenda_cria_registo(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->bot->responder($this->tenant, '+258841111111', '1');

        $this->assertDatabaseHas('encomendas', [
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258841111111',
            'estado' => 'pendente',
        ]);
    }

    public function test_pesquisa_retorna_resultados(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '2');
        $texto = $this->getResposta($resposta);
        $this->assertNotEmpty($texto);
    }

    public function test_menu_opcao_3_mostra_encomendas(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '3');
        $texto = $this->getResposta($resposta);
        $this->assertNotEmpty($texto);
    }

    public function test_menu_opcao_4_mostra_vendedores(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '4');
        $texto = $this->getResposta($resposta);
        $this->assertStringContainsString('Vendedor', $texto);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
