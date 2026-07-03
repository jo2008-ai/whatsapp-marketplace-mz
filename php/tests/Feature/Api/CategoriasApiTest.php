<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoriasApiTest extends TestCase
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

    public function test_listar_categorias(): void
    {
        Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/categorias');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data');
    }

    public function test_criar_categoria(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/loja/categorias', [
                'nome' => 'Verduras',
                'descricao' => 'Hortaliças frescas',
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('categorias', ['nome' => 'Verduras']);
    }

    public function test_criar_categoria_nome_obrigatorio(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/loja/categorias', [
                'descricao' => 'Sem nome',
            ]);

        $response->assertStatus(422);
    }

    public function test_ver_categoria(): void
    {
        $categoria = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/loja/categorias/{$categoria->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.nome', 'Frutas');
    }

    public function test_ver_categoria_inexistente(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/categorias/99999');

        $response->assertStatus(404);
    }

    public function test_actualizar_categoria(): void
    {
        $categoria = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/loja/categorias/{$categoria->id}", [
                'nome' => 'Frutas Tropicais',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('categorias', ['nome' => 'Frutas Tropicais']);
    }

    public function test_eliminar_categoria(): void
    {
        $categoria = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Temporária',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/loja/categorias/{$categoria->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('categorias', ['id' => $categoria->id]);
    }

    public function test_categoria_de_outro_tenant_nao_acessivel(): void
    {
        $outroTenant = Tenant::create([
            'nome_loja' => 'Outra Loja',
            'email_dono' => 'outra@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $categoria = Categoria::create([
            'tenant_id' => $outroTenant->id,
            'nome' => 'Categoria Alheia',
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/loja/categorias/{$categoria->id}");

        $response->assertStatus(404);
    }
}
