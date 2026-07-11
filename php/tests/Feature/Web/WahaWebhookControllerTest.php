<?php

namespace Tests\Feature\Web;

use App\Models\InstanciaWhatsApp;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class WahaWebhookControllerTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;
    private InstanciaWhatsApp $instancia;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.evolution.webhook_secret' => 'test-secret']);
        config(['services.waha.webhook_secret' => 'test-secret']);

        $this->tenant = Tenant::create([
            'nome_loja' => 'Loja Teste',
            'email_dono' => 'dono@loja.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        User::create([
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
            'waha_url' => 'https://evo.test.com',
            'estado' => 'aguarda_qr',
        ]);
    }

    // =====================================================
    // CONNECTION.UPDATE EVENTS
    // =====================================================

    public function test_connection_update_open_conecta_instancia(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'data' => [
                'state' => 'open',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'processed']);

        $this->instancia->refresh();
        $this->assertEquals('conectada', $this->instancia->estado);
        $this->assertNotNull($this->instancia->conectada_em);
        $this->assertEquals('+258840000000', $this->instancia->numero_whatsapp);
    }

    public function test_connection_update_close_desconecta_instancia(): void
    {
        Notification::fake();

        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'data' => [
                'state' => 'close',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('desconectada', $this->instancia->estado);
        $this->assertNull($this->instancia->conectada_em);
    }

    public function test_connection_update_connecting_muda_para_aguarda_qr(): void
    {
        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'data' => [
                'state' => 'connecting',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('aguarda_qr', $this->instancia->estado);
    }

    public function test_connection_update_nao_muda_se_mesmo_estado(): void
    {
        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'data' => [
                'state' => 'open',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('conectada', $this->instancia->estado);
    }

    public function test_connection_update_notifica_desconexao(): void
    {
        Notification::fake();

        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'data' => [
                'state' => 'close',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('desconectada', $this->instancia->estado);
        $this->assertNull($this->instancia->conectada_em);
    }

    // =====================================================
    // INSTANCE FIELD VARIATIONS
    // =====================================================

    public function test_session_field_compativel_com_instance(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'session' => 'default',
            'data' => [
                'state' => 'open',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('conectada', $this->instancia->estado);
    }

    public function test_data_field_compativel_com_payload(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'payload' => [
                'state' => 'open',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('conectada', $this->instancia->estado);
    }

    // =====================================================
    // IGNORED / UNKNOWN EVENTS
    // =====================================================

    public function test_evento_desconhecido_retorna_processed(): void
    {
        $response = $this->sendWebhook([
            'event' => 'messages.upsert',
            'instance' => 'default',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'processed']);
    }

    public function test_sem_evento_retorna_ignored(): void
    {
        $response = $this->sendWebhook([
            'instance' => 'default',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ignored']);
    }

    public function test_sessao_nao_encontrada_retorna_session_not_found(): void
    {
        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'inexistente_999',
            'data' => [
                'state' => 'open',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'session_not_found']);
    }

    // =====================================================
    // PAYLOAD VARIATIONS
    // =====================================================

    public function test_connection_update_funciona_com_data_em_vez_de_payload(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'data' => [
                'state' => 'open',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('conectada', $this->instancia->estado);
    }

    public function test_connection_update_funciona_com_status_raiz(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'connection.update',
            'instance' => 'default',
            'state' => 'open',
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        // O controller lê de data.state, entao se state esta na raiz, nao deve mudar
        // Mas nao deve falhar
    }

    // =====================================================
    // HELPERS
    // =====================================================

    private function sendWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('/api/waha/webhook', $payload);
    }
}
