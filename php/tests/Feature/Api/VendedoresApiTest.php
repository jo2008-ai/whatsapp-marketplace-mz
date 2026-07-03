<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendedoresApiTest extends TestCase
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
    }

    private function authHeader(): array
    {
        $token = $this->user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_listar_vendedores(): void
    {
        Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'João',
            'numero_whatsapp' => '+258840000000',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/vendedores');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_criar_vendedor(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/loja/vendedores', [
                'nome' => 'Maria',
                'numero_whatsapp' => '+258841111111',
                'descricao' => 'Vendedora principal',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('vendedores', ['nome' => 'Maria']);
    }

    public function test_criar_vendedor_campos_obrigatorios(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/loja/vendedores', [
                'descricao' => 'Sem nome nem número',
            ]);

        $response->assertStatus(422);
    }

    public function test_ver_vendedor(): void
    {
        $vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'João',
            'numero_whatsapp' => '+258840000000',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/loja/vendedores/{$vendedor->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.nome', 'João');
    }

    public function test_ver_vendedor_inexistente(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/vendedores/99999');

        $response->assertStatus(404);
    }

    public function test_actualizar_vendedor(): void
    {
        $vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'João',
            'numero_whatsapp' => '+258840000000',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/loja/vendedores/{$vendedor->id}", [
                'nome' => 'João Silva',
                'numero_whatsapp' => '+258840000000',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('vendedores', ['nome' => 'João Silva']);
    }

    public function test_eliminar_vendedor(): void
    {
        $vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Temporário',
            'numero_whatsapp' => '+258840000000',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/loja/vendedores/{$vendedor->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('vendedores', ['id' => $vendedor->id]);
    }

    public function test_toggle_vendedor(): void
    {
        $vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'João',
            'numero_whatsapp' => '+258840000000',
            'ativo' => true,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/vendedores/{$vendedor->id}/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('data.ativo', false);
    }

    public function test_vendedor_de_outro_tenant_nao_acessivel(): void
    {
        $outroTenant = Tenant::create([
            'nome_loja' => 'Outra Loja',
            'email_dono' => 'outra@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $vendedor = Vendedor::create([
            'tenant_id' => $outroTenant->id,
            'nome' => 'Vendedor Alheio',
            'numero_whatsapp' => '+258842222222',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/loja/vendedores/{$vendedor->id}");

        $response->assertStatus(404);
    }
}
