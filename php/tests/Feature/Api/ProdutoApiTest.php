<?php

namespace Tests\Feature\Api;

use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProdutoApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Tenant $tenant;
    private Categoria $categoria;
    private Vendedor $vendedor;

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

        $this->categoria = Categoria::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Frutas',
        ]);

        $this->vendedor = Vendedor::create([
            'tenant_id' => $this->tenant->id,
            'nome' => 'Vendedor',
            'numero_whatsapp' => '+258840000000',
        ]);
    }

    private function authHeader(): array
    {
        $token = $this->user->createToken('test')->plainTextToken;
        return ['Authorization' => "Bearer {$token}"];
    }

    public function test_listar_produtos(): void
    {
        Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $this->categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson('/api/loja/produtos');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonCount(1, 'data.data');
    }

    public function test_criar_produto(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/loja/produtos', [
                'nome' => 'Maça',
                'preco' => 120,
                'stock' => 20,
                'categoria_id' => $this->categoria->id,
                'vendedor_id' => $this->vendedor->id,
                'imagem' => UploadedFile::fake()->create('maca.jpg', 100, 'image/jpeg'),
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('produtos', ['nome' => 'Maça']);
    }

    public function test_criar_produto_preco_negativo_falha(): void
    {
        $response = $this->withHeaders($this->authHeader())
            ->postJson('/api/loja/produtos', [
                'nome' => 'Maça',
                'preco' => -10,
                'stock' => 20,
                'categoria_id' => $this->categoria->id,
                'vendedor_id' => $this->vendedor->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_toggle_disponivel(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $this->categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
            'disponivel' => true,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->patchJson("/api/loja/produtos/{$produto->id}/toggle");

        $response->assertStatus(200)
            ->assertJsonPath('data.disponivel', false);
    }

    public function test_atualizar_produto(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $this->categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->putJson("/api/loja/produtos/{$produto->id}", [
                'nome' => 'Banana Premium',
                'preco' => 80,
                'stock' => 15,
                'categoria_id' => $this->categoria->id,
                'vendedor_id' => $this->vendedor->id,
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('produtos', ['nome' => 'Banana Premium', 'preco' => 80]);
    }

    public function test_remover_produto(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $this->categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->deleteJson("/api/loja/produtos/{$produto->id}");

        $response->assertStatus(200);
        $this->assertSoftDeleted('produtos', ['id' => $produto->id]);
    }

    public function test_produto_de_outro_tenant_nao_acessivel(): void
    {
        $outroTenant = Tenant::create([
            'nome_loja' => 'Outra Loja',
            'email_dono' => 'outra@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $produto = Produto::create([
            'tenant_id' => $outroTenant->id,
            'nome' => 'Produto Alheio',
            'preco' => 100,
            'stock' => 5,
        ]);

        $response = $this->withHeaders($this->authHeader())
            ->getJson("/api/loja/produtos/{$produto->id}");

        $response->assertStatus(404);
    }
}
