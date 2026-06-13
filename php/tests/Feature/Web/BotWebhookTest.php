<?php

namespace Tests\Feature\Web;

use App\Models\Categoria;
use App\Models\InstanciaWhatsApp;
use App\Models\Produto;
use App\Models\Tenant;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BotWebhookTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private InstanciaWhatsApp $instancia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'nome_loja' => 'Teste',
            'email_dono' => 'loja@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $this->instancia = InstanciaWhatsApp::create([
            'tenant_id' => $this->tenant->id,
            'nome_instancia' => 'loja_' . $this->tenant->id . '_test123',
            'evolution_instance_name' => 'loja_' . $this->tenant->id . '_test123',
            'estado' => 'conectada',
        ]);

        $cat = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
        ]);

        $vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Vendedor',
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
    }

    public function test_webhook_retorna_resposta(): void
    {
        $response = $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '+258841111111',
            'mensagem' => 'olá',
            'nome' => 'Cliente Teste',
        ]);

        $response->assertStatus(200)
            ->assertJson(['enviar' => true])
            ->assertJsonStructure(['resposta']);
    }

    public function test_webhook_instancia_inexistente_falha(): void
    {
        $response = $this->postJson('/api/mensagem', [
            'instance_name' => 'inexistente_999_abc',
            'numero' => '+258841111111',
            'mensagem' => 'olá',
        ]);

        $response->assertStatus(200)
            ->assertJson(['enviar' => true]);
    }

    public function test_webhook_grupo_nao_responde(): void
    {
        $response = $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '120363001234567@g.us',
            'mensagem' => 'olá',
            'is_grupo' => true,
        ]);

        $response->assertStatus(200)
            ->assertJson(['enviar' => false]);
    }

    public function test_webhook_regista_log(): void
    {
        $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '+258841111111',
            'mensagem' => 'olá',
        ]);

        $this->assertDatabaseHas('logs_bot', [
            'tenant_id' => $this->tenant->id,
            'numero_whatsapp' => '+258841111111',
            'direcao' => 'entrada',
        ]);
    }

    public function test_webhook_mensagem_obrigatoria(): void
    {
        $response = $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '+258841111111',
        ]);

        $response->assertStatus(422);
    }

    public function test_webhook_tenant_inactivo_responde_indisponivel(): void
    {
        $this->tenant->update(['estado' => 'suspenso']);

        $response = $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '+258841111111',
            'mensagem' => 'olá',
        ]);

        $response->assertStatus(200)
            ->assertJson(['enviar' => true])
            ->assertJsonFragment(['resposta' => 'Serviço temporariamente indisponível.']);
    }

    public function test_webhook_fluxo_completo_pedido(): void
    {
        $response = $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '+258841111111',
            'mensagem' => '1',
            'nome' => 'Maria',
        ]);

        $response->assertStatus(200)
            ->assertJson(['enviar' => true]);

        $response2 = $this->postJson('/api/mensagem', [
            'instance_name' => $this->instancia->evolution_instance_name,
            'numero' => '+258841111111',
            'mensagem' => '1',
        ]);

        $response2->assertStatus(200)
            ->assertJson(['enviar' => true]);
    }
}
