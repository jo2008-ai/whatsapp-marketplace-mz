<?php

namespace Tests\Feature\Web;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use App\Services\WahaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Mockery;
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
            'waha_session' => "loja-{$this->tenant->id}",
            'waha_url' => 'https://waha.test.com',
            'estado' => 'aguarda_qr',
        ]);

        config(['services.waha.key' => 'test-api-key']);
        config(['services.waha.url' => 'https://waha.test.com']);
        config(['services.waha.webhook_secret' => 'test-webhook-secret']);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function auth(): void
    {
        $this->actingAs($this->user);
    }

    private function mockWahaService(): WahaService
    {
        $mock = Mockery::mock(WahaService::class);
        $this->app->instance(WahaService::class, $mock);
        return $mock;
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

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('ligar')
            ->once()
            ->with($this->tenant->id)
            ->andReturn(true);

        $response = $this->post('/painel/whatsapp/conectar');

        $response->assertRedirect();
        $this->assertDatabaseHas('instancias_whatsapp', [
            'tenant_id' => $this->tenant->id,
            'estado' => 'aguarda_qr',
        ]);
    }

    public function test_conectar_redireciona_se_instancia_existe(): void
    {
        $this->auth();

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('ligar')
            ->once()
            ->with($this->tenant->id)
            ->andReturn(true);

        $response = $this->post('/painel/whatsapp/conectar');

        $response->assertRedirect();
        $response->assertSessionHas('success');
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

    public function test_qr_retorna_503_se_waha_indisponivel(): void
    {
        $this->auth();

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andThrow(new \Exception('Connection refused'));

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(503)
            ->assertJson(['erro' => 'Servico indisponivel']);
    }

    public function test_qr_retorna_qr_code_quando_sessao_esta_pronta(): void
    {
        $this->auth();

        $fakeQr = base64_encode('fake-qr-code');

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andReturn('SCAN_QR_CODE');
        $wahaMock->shouldReceive('obterQrCode')
            ->once()
            ->with($this->tenant->id)
            ->andReturn($fakeQr);

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

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andReturn('STARTING');
        $wahaMock->shouldReceive('obterQrCode')
            ->once()
            ->with($this->tenant->id)
            ->andReturn(null);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'aguarda_qr',
                'qr' => null,
            ]);
    }

    public function test_qr_inicia_sessao_se_estado_nao_e_starting(): void
    {
        $this->auth();

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andReturn('NOT_FOUND');
        $wahaMock->shouldReceive('ligar')
            ->once()
            ->with($this->tenant->id)
            ->andReturn(true);

        $response = $this->getJson('/painel/whatsapp/qr?instancia=' . $this->instancia->id);

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'aguarda_qr',
                'mensagem' => 'Sessao a iniciar...',
            ]);
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

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andReturn('WORKING');

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

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andReturn('SCAN_QR_CODE');

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertStatus(200)
            ->assertJson([
                'estado' => 'desconectada',
                'state' => 'SCAN_QR_CODE',
            ]);
    }

    public function test_estado_retorna_erro_se_waha_indisponivel(): void
    {
        $this->auth();

        $wahaMock = $this->mockWahaService();
        $wahaMock->shouldReceive('obterEstado')
            ->once()
            ->with($this->tenant->id)
            ->andReturn('ERROR');

        $response = $this->getJson('/painel/whatsapp/estado');

        $response->assertStatus(200)
            ->assertJson(['estado' => 'erro']);
    }
}
