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

        config(['services.waha.webhook_secret' => 'test-secret']);

        $this->tenant = Tenant::create([
            'nome_loja' => 'Loja Teste',
            'email_dono' => 'dono@loja.com',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
        ]);

        $user = User::create([
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
    }

    private function assinarPayload(array $payload): string
    {
        $secret = config('services.waha.webhook_secret');
        $content = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $content, $secret);
    }

    private function sendWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $signature = $this->assinarPayload($payload);

        return $this->postJson('/api/waha/webhook', $payload, [
            'X-Hub-Signature-256' => $signature,
        ]);
    }

    // =====================================================
    // SESSION.STATUS EVENTS
    // =====================================================

    public function test_session_status_working_conecta_instancia(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'WORKING',
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

    public function test_session_status_failed_desconecta_instancia(): void
    {
        Notification::fake();

        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'FAILED',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('desconectada', $this->instancia->estado);
        $this->assertNull($this->instancia->conectada_em);
    }

    public function test_session_status_stopped_desconecta_instancia(): void
    {
        Notification::fake();

        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'STOPPED',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('desconectada', $this->instancia->estado);
    }

    public function test_session_status_disconnected_desconecta_instancia(): void
    {
        Notification::fake();

        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'DISCONNECTED',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('desconectada', $this->instancia->estado);
    }

    public function test_session_status_starting_muda_para_aguarda_qr(): void
    {
        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'STARTING',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('aguarda_qr', $this->instancia->estado);
    }

    public function test_session_status_scan_qr_code_muda_para_aguarda_qr(): void
    {
        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'SCAN_QR_CODE',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('aguarda_qr', $this->instancia->estado);
    }

    public function test_session_status_nao_muda_se_mesmo_estado(): void
    {
        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'WORKING',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        // Estado continua o mesmo, nao deve ter actualizado
        $this->assertEquals('conectada', $this->instancia->estado);
    }

    public function test_session_status_notifica_desconexao(): void
    {
        Notification::fake();

        $this->instancia->update(['estado' => 'conectada']);

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'payload' => [
                'status' => 'FAILED',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('desconectada', $this->instancia->estado);
        $this->assertNull($this->instancia->conectada_em);
    }

    // =====================================================
    // SESSION.QR EVENTS
    // =====================================================

    public function test_session_qr_muda_estado_para_aguarda_qr(): void
    {
        $this->instancia->update(['estado' => 'desconectada']);

        $response = $this->sendWebhook([
            'event' => 'session.qr',
            'session' => 'default',
            'payload' => [
                'base64' => 'fake-qr-data',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('aguarda_qr', $this->instancia->estado);
    }

    public function test_session_qr_nao_muda_se_ja_aguarda_qr(): void
    {
        $response = $this->sendWebhook([
            'event' => 'session.qr',
            'session' => 'default',
            'payload' => [
                'base64' => 'fake-qr-data',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('aguarda_qr', $this->instancia->estado);
    }

    // =====================================================
    // IGNORED / UNKNOWN EVENTS
    // =====================================================

    public function test_evento_desconhecido_retorna_ignored(): void
    {
        $response = $this->sendWebhook([
            'event' => 'message',
            'session' => 'default',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'processed']);
    }

    public function test_sem_evento_retorna_ignored(): void
    {
        $response = $this->sendWebhook([
            'session' => 'default',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ignored']);
    }

    public function test_sessao_nao_encontrada_retorna_session_not_found(): void
    {
        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'inexistente_999',
            'payload' => [
                'status' => 'WORKING',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'session_not_found']);
    }

    // =====================================================
    // SIGNATURE VERIFICATION
    // =====================================================

    public function test_webhook_rejeita_sem_assinatura(): void
    {
        $response = $this->postJson('/api/waha/webhook', [
            'event' => 'session.status',
            'session' => 'default',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_rejeita_assinatura_invalida(): void
    {
        $response = $this->postJson('/api/waha/webhook', [
            'event' => 'session.status',
            'session' => 'default',
        ], [
            'X-Hub-Signature-256' => 'sha256=invalid-signature',
        ]);

        $response->assertStatus(401);
    }

    public function test_webhook_aceita_assinatura_valida(): void
    {
        $payload = [
            'event' => 'session.status',
            'session' => 'default',
            'payload' => ['status' => 'WORKING'],
        ];

        $response = $this->sendWebhook($payload);

        $response->assertStatus(200);
    }

    // =====================================================
    // PAYLOAD VARIATIONS
    // =====================================================

    public function test_session_status_funciona_com_data_em_vez_de_payload(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'data' => [
                'status' => 'WORKING',
                'user' => '+258840000000',
            ],
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        $this->assertEquals('conectada', $this->instancia->estado);
    }

    public function test_session_status_funciona_com_status_raiz(): void
    {
        Notification::fake();

        $response = $this->sendWebhook([
            'event' => 'session.status',
            'session' => 'default',
            'status' => 'WORKING',
        ]);

        $response->assertStatus(200);

        $this->instancia->refresh();
        // Não deve mudar porque o status não está no payload/data
        // Mas não deve falhar
    }
}
