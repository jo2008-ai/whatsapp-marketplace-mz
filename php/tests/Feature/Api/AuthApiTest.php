<?php

namespace Tests\Feature\Api;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $tenant = Tenant::create([
            'nome_loja' => 'Teste',
            'email_dono' => 'loja@teste.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $this->user = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Admin Teste',
            'email' => 'admin@teste.com',
            'password' => 'password123',
            'role' => 'admin',
        ]);
    }

    public function test_login_com_credenciais_validas(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@teste.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonStructure(['data' => ['user', 'token']]);
    }

    public function test_login_com_credenciais_invalidas(): void
    {
        $response = $this->postJson('/api/auth/login', [
            'email' => 'admin@teste.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    public function test_me_com_token_valido(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me');

        $response->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonPath('data.email', 'admin@teste.com');
    }

    public function test_me_sem_token(): void
    {
        $response = $this->getJson('/api/auth/me');
        $response->assertStatus(401);
    }

    public function test_logout_invalida_token(): void
    {
        $token = $this->user->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/auth/logout')
            ->assertStatus(200);

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/auth/me')
            ->assertStatus(401);
    }
}
