<?php

namespace Tests\Unit\Actions\Produto;

use App\Actions\Produto\ActualizarProduto;
use App\Actions\Produto\CriarProduto;
use App\Actions\Produto\EliminarProduto;
use App\Models\Categoria;
use App\Models\Produto;
use App\Models\Tenant;
use App\Models\Vendedor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ProdutoActionTest extends TestCase
{
    use RefreshDatabase;

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

    private function fakeImage(): UploadedFile
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tempFile, base64_decode('/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxwcKDcpLDAxNDQ0Hyc5PTgyPC4zNDL/2wBDAQkJCQwLDBgNDRgyIRwhMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjIyMjL/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL/8QAFRABAQAAAAAAAAAAAAAAAAAAAAf/xAAUAQEAAAAAAAAAAAAAAAAAAAAA/8QAFBEBAAAAAAAAAAAAAAAAAAAAAP/aAAwDAQACEQMRAD8AaA//2Q=='));
        return new UploadedFile($tempFile, 'maca.jpg', 'image/jpeg', null, true);
    }

    public function test_criar_produto(): void
    {
        $action = app(CriarProduto::class);

        $produto = $action->handle(
            $this->tenant,
            [
                'nome' => 'Banana',
                'preco' => 50,
                'stock' => 10,
                'categoria_id' => $this->categoria->id,
                'vendedor_id' => $this->vendedor->id,
            ],
            $this->fakeImage()
        );

        $this->assertDatabaseHas('produtos', ['nome' => 'Banana', 'tenant_id' => $this->tenant->id]);
        $this->assertEquals('Banana', $produto->nome);
        $this->assertEquals($this->tenant->id, $produto->tenant_id);
    }

    public function test_criar_produto_com_imagem_url_guardada_no_media_library(): void
    {
        $action = app(CriarProduto::class);

        $produto = $action->handle(
            $this->tenant,
            [
                'nome' => 'Maça',
                'preco' => 120,
                'stock' => 20,
                'categoria_id' => $this->categoria->id,
                'vendedor_id' => $this->vendedor->id,
            ],
            $this->fakeImage()
        );

        $this->assertCount(1, $produto->getMedia('imagens'));
    }

    public function test_criar_produto_sem_imagem(): void
    {
        $action = app(CriarProduto::class);

        $produto = $action->handle(
            $this->tenant,
            [
                'nome' => 'Laranja',
                'preco' => 30,
                'stock' => 5,
            ]
        );

        $this->assertDatabaseHas('produtos', ['nome' => 'Laranja']);
        $this->assertCount(0, $produto->getMedia('imagens'));
    }

    public function test_actualizar_produto(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $this->categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
        ]);

        $action = app(ActualizarProduto::class);

        $actualizado = $action->handle(
            $this->tenant,
            $produto->id,
            [
                'nome' => 'Banana Premium',
                'preco' => 80,
                'stock' => 15,
            ]
        );

        $this->assertNotNull($actualizado);
        $this->assertEquals('Banana Premium', $actualizado->nome);
        $this->assertEquals(80, $actualizado->preco);
        $this->assertEquals(15, $actualizado->stock);
    }

    public function test_actualizar_produto_inexistente_retorna_null(): void
    {
        $action = app(ActualizarProduto::class);

        $resultado = $action->handle(
            $this->tenant,
            999999,
            ['nome' => 'Inexistente']
        );

        $this->assertNull($resultado);
    }

    public function test_eliminar_produto(): void
    {
        $produto = Produto::create([
            'tenant_id' => $this->tenant->id,
            'categoria_id' => $this->categoria->id,
            'vendedor_id' => $this->vendedor->id,
            'nome' => 'Banana',
            'preco' => 50,
            'stock' => 10,
        ]);

        $action = app(EliminarProduto::class);

        $resultado = $action->handle($this->tenant, $produto->id);

        $this->assertTrue($resultado);
        $this->assertSoftDeleted('produtos', ['id' => $produto->id]);
    }

    public function test_eliminar_produto_inexistente_retorna_false(): void
    {
        $action = app(EliminarProduto::class);

        $resultado = $action->handle($this->tenant, 999999);

        $this->assertFalse($resultado);
    }
}
