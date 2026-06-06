<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\Encomenda;
use App\Models\Produto;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EncomendaApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;

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

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin',
            'email' => 'admin@teste.com',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function authHeader(): array
    {
        $token = $this->user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_listar_encomendas(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Teste',
            'preco' => 100,
            'stock' => 10,
        ]);

        Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258841111111',
            'nome_cliente' => 'Cliente',
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/encomendas');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.data');
    }

    public function test_filtrar_por_estado(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Teste',
            'preco' => 100,
            'stock' => 10,
        ]);

        Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258841111111',
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258842222222',
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco_total' => 100,
            'estado' => 'entregue',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/encomendas?estado=pendente');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_actualizar_estado_encomenda(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Teste',
            'preco' => 100,
            'stock' => 10,
        ]);

        $encomenda = Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258841111111',
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/encomendas/{$encomenda->id}/estado", [
                'estado' => 'confirmada',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('encomendas', [
            'id' => $encomenda->id,
            'estado' => 'confirmada',
        ]);
    }

    public function test_estado_invalido_falha(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Teste',
            'preco' => 100,
            'stock' => 10,
        ]);

        $encomenda = Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'numero_cliente' => '+258841111111',
            'produto_id' => $produto->id,
            'quantidade' => 1,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/encomendas/{$encomenda->id}/estado", [
                'estado' => 'invalido',
            ]);

        $response->assertStatus(422);
    }
}
