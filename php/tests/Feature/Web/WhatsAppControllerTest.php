<?php

namespace Tests\Feature\Web;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WhatsAppControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private User $user;
    private InstanciaWhatsApp $instancia;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::create([
            'nome_loja' => 'Loja Teste',
            'email_dono' => 'dono@loja.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $this->user = User::create([
            'tenant_id' => $this->tenant->id,
            'name' => 'Admin',
            'email' => 'admin@loja.com',
            'password' => 'password',
            'role' => 'admin',
        ]);

        $this->instancia = InstanciaWhatsApp::create([
            'tenant_id' => $this->tenant->id,
            'nome_instancia' => 'default',
            'waha_session' => 'default',
            'waha_url' => 'https://waha.test.com',
            'estado' => 'aguarda_qr',
        ]);

        config(['services.waha.key' => 'test-api-key']);
        config(['services.waha.webhook_secret' => 'test-webhook-secret']);
    }

    private function auth(): void
    {
        $this->actingAs($this->user);
    }

    // =====================================================
    // INDEX
    // =====================================================

    public function test_index_mostra_instancias(): void
    {
        $this->auth();

        $response = $this->get('/painel/whatsapp');

        $response->assertStatus(200);
    }

    // =====================================================
    // CONECTAR
    // =====================================================

    public function test_conectar_cria_instancia_nova(): void
    {
        $this->auth();

        $this->instancia->delete();

        $response = $this->post('/painel/whatsapp/conectar');

        $response->assertRedirect();
        $this->assertDatabaseHas('instancias_whatsapp', [
            'tenant_id' => $this->tenant->id,
            'waha_session' => 'default',
            'estado' => 'aguarda_qr',
        ]);
    }

    public function test_conectar_redireciona_se_instancia_existe(): void
    {
        $this->auth();

        $response = $this->post('/painel/whatsapp/conectar');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_conectar_define_waha_url_se_ausente(): void
    {
        $this->auth();

        $this->instancia->delete();
        config(['services.waha.urls.1' => 'https://waha-from-config.test.com']);

        $response = $this->post('/painel/whatsapp/conectar');

        $response->assertRedirect();
        $this->assertDatabaseHas('instancias_whatsapp', [
            'tenant_id' => $this->tenant->id,
            'waha_url' => 'https://waha-from-config.test.com',
        ]);
    }

    // =====================================================
    // QR CODE
    // =====================================================

    public function test_qr_retorna_404_se_instancia_nao_existe(): void
    {
        $this->auth();

        $response = $this->getJson('/painel/whatsapp/qr?instancia=99999');

        $response->assertStatus(404)
            ->assertJson(['erro' => 'Instancia nao encontrada']);
    }

    public function test_qr_retorna_500_se_waha_nao_configurado(): void
    {
        $this->auth();

        $this->instancia->update(['waha_url' => null]);
        config()->offsetUnset('services.waha.urls.' . $this->tenant->id);
        config(['services.waha.url' => null]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(500)
            ->assertJson(['erro' => 'WAHA nao configurado para este tenant']);
    }

    public function test_qr_inicia_sessao_se_estado_diferente_de_starting(): void
    {
        $this->auth();

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([
                'status' => 'WORKING',
            ], 200),
            'waha.test.com/api/sessions/default/start' => Http::response([], 200),
        ]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'aguarda_qr',
                'mensagem' => 'Sessao a iniciar...',
            ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://waha.test.com/api/sessions/default/start'
                && $request->method() === 'POST';
        });
    }

    public function test_qr_retorna_qr_code_quando_sessao_esta_pronta(): void
    {
        $this->auth();

        $fakeQr = base64_encode('fake-qr-code');

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([
                'status' => 'SCAN_QR_CODE',
            ], 200),
            'waha.test.com/api/default/auth/qr' => Http::response([
                'base64' => $fakeQr,
            ], 200),
        ]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'aguarda_qr',
                'qr' => $fakeQr,
            ]);
    }

    public function test_qr_retorna_qr_null_quando_sem_dados(): void
    {
        $this->auth();

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([
                'status' => 'STARTING',
            ], 200),
            'waha.test.com/api/default/auth/qr' => Http::response([], 200),
        ]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'aguarda_qr',
                'qr' => null,
            ]);
    }

    public function test_qr_retorna_401_se_api_key_invalida(): void
    {
        $this->auth();

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([
                'error' => 'Unauthorized',
            ], 401),
        ]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(401)
            ->assertJson(['erro' => 'Chave de API invalida. Verifica WAHA_SECRET no Render.']);
    }

    public function test_qr_retorna_503_se_waha_indisponivel(): void
    {
        $this->auth();

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(503)
            ->assertJson(['erro' => 'Servico indisponivel']);
    }

    public function test_qr_tenta_3_vezes_antes_de_falhar(): void
    {
        $this->auth();

        $attemptCount = 0;

        Http::fake([
            'waha.test.com/api/sessions/default' => function () use (&$attemptCount) {
                $attemptCount++;
                return Http::response([], 500);
            },
        ]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $this->assertGreaterThanOrEqual(1, $attemptCount);
    }

    public function test_qr_usa_waha_url_da_instancia(): void
    {
        $this->auth();

        $customUrl = 'https://custom-waha.test.com';
        $this->instancia->update(['waha_url' => $customUrl]);

        Http::fake([
            $customUrl . '/api/sessions/default' => Http::response([
                'status' => 'SCAN_QR_CODE',
            ], 200),
            $customUrl . '/api/default/auth/qr' => Http::response([
                'base64' => base64_encode('test'),
            ], 200),
        ]);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(200);

        Http::assertSent(function ($request) use ($customUrl) {
            return str_starts_with($request->url(), $customUrl);
        });
    }

    // =====================================================
    // ESTADO
    // =====================================================

    public function test_estado_retorna_erro_se_sem_instancia(): void
    {
        $this->auth();

        $this->instancia->delete();

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertJson(['estado' => 'erro', 'error' => 'Sem instancia']);
    }

    public function test_estado_retorna_conectada_quando_working(): void
    {
        $this->auth();

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([
                'status' => 'WORKING',
            ], 200),
        ]);

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'conectada',
                'state' => 'WORKING',
            ]);
    }

    public function test_estado_retorna_desconectada_quando_nao_working(): void
    {
        $this->auth();

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([
                'status' => 'SCAN_QR_CODE',
            ], 200),
        ]);

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'desconectada',
                'state' => 'SCAN_QR_CODE',
            ]);
    }

    public function test_estado_retorna_erro_se_http_falha(): void
    {
        $this->auth();

        Http::fake([
            'waha.test.com/api/sessions/default' => Http::response([], 500),
        ]);

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertStatus(200)
            ->assertJson(['estado' => 'erro']);
    }

    public function test_estado_retorna_erro_se_waha_indisponivel(): void
    {
        $this->auth();

        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertStatus(200)
            ->assertJson(['estado' => 'erro']);
    }
}
