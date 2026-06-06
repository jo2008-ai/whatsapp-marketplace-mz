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
        $notificacaoMock->shouldReceive('notificarVendedor')->andReturn();
        $this->bot = new BotService($notificacaoMock);

        $this->tenant = Tenant::create([
            'nome_loja' => 'Teste Loja',
            'email_dono' => 'teste@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
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

    public function test_saudacao_retorna_menu(): void
    {
        $resposta = $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $this->assertStringContainsString('Ver produtos', $resposta);
        $this->assertStringContainsString('Pesquisar', $resposta);
    }

    public function test_loja_suspensa_retorna_indisponivel(): void
    {
        $this->tenant->update(['estado' => 'suspenso']);
        $resposta = $this->bot->responder($this->tenant, '+258841111111', 'olá');
        $this->assertStringContainsString('indisponível', $resposta);
    }

    public function test_menu_opcao_1_mostra_categorias(): void
    {
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->assertStringContainsString('Frutas', $resposta);
    }

    public function test_categoria_selecionada_mostra_produtos(): void
    {
        // Primeiro menu
        $this->bot->responder($this->tenant, '+258841111111', '1');
        // Selecionar categoria 1
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1');
        $this->assertStringContainsString('Banana', $resposta);
    }

    public function test_produto_sem_stock_nao_permite_encomendar(): void
    {
        // Navegar até produto sem stock (Maça)
        $this->bot->responder($this->tenant, '+258841111111', '1'); // menu -> categorias
        $this->bot->responder($this->tenant, '+258841111111', '1'); // categorias -> produtos
        $this->bot->responder($this->tenant, '+258841111111', '2'); // selecionar Maça (stock=0)
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1'); // tentar encomendar
        $this->assertStringContainsString('sem stock', mb_strtolower($resposta));
    }

    public function test_encomenda_cria_registo(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', '1'); // menu
        $this->bot->responder($this->tenant, '+258841111111', '1'); // categorias
        $this->bot->responder($this->tenant, '+258841111111', '1'); // produto (Banana)
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '1'); // encomendar

        $this->assertStringContainsString('Encomenda feita', $resposta);
        $this->assertDatabaseHas('encomendas', [
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258841111111',
            'estado' => 'pendente',
        ]);
    }

    public function test_pesquisa_retorna_resultados(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', '2'); // menu -> pesquisa
        $resposta = $this->bot->responder($this->tenant, '+258841111111', 'banana');
        $this->assertStringContainsString('Banana', $resposta);
    }

    public function test_pesquisa_sem_resultados(): void
    {
        $this->bot->responder($this->tenant, '+258841111111', '2');
        $resposta = $this->bot->responder($this->tenant, '+258841111111', 'xyzabc');
        $this->assertStringContainsString('Nenhum produto', $resposta);
    }

    public function test_menu_opcao_3_mostra_encomendas(): void
    {
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '3');
        $this->assertStringContainsString('encomendas', mb_strtolower($resposta));
    }

    public function test_menu_opcao_4_mostra_vendedores(): void
    {
        $resposta = $this->bot->responder($this->tenant, '+258841111111', '4');
        $this->assertStringContainsString('Vendedor Teste', $resposta);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
