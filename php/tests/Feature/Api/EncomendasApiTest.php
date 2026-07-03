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

class EncomendasApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Produto $produto;
    private Vendedor $vendedor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'nome_loja' => 'Teste',
            'email_dono' => 'loja@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'activo' => true,
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

        $categoria = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
        ]);

        $this->vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'João',
            'numero_whatsapp' => '+258840000000',
        ]);

        $this->produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
        ]);
    }

    private function authHeader(): array
    {
        $token = $this->user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_listar_encomendas(): void
    {
        Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'produto_id' => $this->produto->id,
            'vendedor_id' => $this->vendedor->id,
            'numero_cliente' => '+258841111111',
            'nome_cliente' => 'Maria',
            'quantidade' => 2,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/encomendas');

        $response->assertStatus(200)
            ->assertJson(['success' => true]);
    }

    public function test_listar_encomendas_por_estado(): void
    {
        Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'produto_id' => $this->produto->id,
            'vendedor_id' => $this->vendedor->id,
            'numero_cliente' => '+258841111111',
            'nome_cliente' => 'Maria',
            'quantidade' => 2,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'produto_id' => $this->produto->id,
            'vendedor_id' => $this->vendedor->id,
            'numero_cliente' => '+258842222222',
            'nome_cliente' => 'José',
            'quantidade' => 1,
            'preco_total' => 50,
            'estado' => 'entregue',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/encomendas?estado=pendente');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data');
    }

    public function test_actualizar_estado_encomenda(): void
    {
        $encomenda = Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'produto_id' => $this->produto->id,
            'vendedor_id' => $this->vendedor->id,
            'numero_cliente' => '+258841111111',
            'nome_cliente' => 'Maria',
            'quantidade' => 2,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/encomendas/{$encomenda->id}/estado", [
                'estado' => 'confirmada',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.estado', 'confirmada');
    }

    public function test_actualizar_estado_invalido(): void
    {
        $encomenda = Encomenda::create([
            'tenant_id' => $this->tenant->id,
            'produto_id' => $this->produto->id,
            'vendedor_id' => $this->vendedor->id,
            'numero_cliente' => '+258841111111',
            'nome_cliente' => 'Maria',
            'quantidade' => 2,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/encomendas/{$encomenda->id}/estado", [
                'estado' => 'inexistente',
            ]);

        $response->assertStatus(422);
    }

    public function test_encomenda_inexistente(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->patchJson('/api/loja/encomendas/99999/estado', [
                'estado' => 'confirmada',
            ]);

        $response->assertStatus(404);
    }

    public function test_encomenda_de_outro_tenant_nao_acessivel(): void
    {
        $outroTenant = Tenant::create([
            'nome_loja' => 'Outra Loja',
            'email_dono' => 'outra@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $outroCategoria = Categoria::create([
            'tenant_id' => $outroTenant->id,
            'nome' => 'Outra',
        ]);

        $outroVendedor = Vendedor::create([
            'tenant_id' => $outroTenant->id,
            'nome' => 'Outro',
            'numero_whatsapp' => '+258843333333',
        ]);

        $outroProduto = Produto::create([
            'tenant_id' => $outroTenant->id,
            'categoria_id' => $outroCategoria->id,
            'vendedor_id' => $outroVendedor->id,
            'nome' => 'Produto Alheio',
            'preco' => 100,
            'stock' => 5,
        ]);

        $encomenda = Encomenda::create([
            'tenant_id' => $outroTenant->id,
            'produto_id' => $outroProduto->id,
            'vendedor_id' => $outroVendedor->id,
            'numero_cliente' => '+258844444444',
            'nome_cliente' => 'Alguém',
            'quantidade' => 1,
            'preco_total' => 100,
            'estado' => 'pendente',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/encomendas/{$encomenda->id}/estado", [
                'estado' => 'confirmada',
            ]);

        $response->assertStatus(404);
    }
}
