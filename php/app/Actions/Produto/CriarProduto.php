<?php

namespace App\Actions\Produto;

use App\Models\Produto;
use App\Models\Tenant;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CriarProduto
{
    public function __construct(
        private ImageService $imageService
    ) {}

    public function handle(Tenant $tenant, array $dados, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): Produto
    {
        return DB::transaction(function () use ($tenant, $dados, $imagem, $imagem2) {
            $dados['tenant_id'] = $tenant->id;

            unset($dados['imagem'], $dados['imagem2'], $dados['imagem_url'], $dados['imagem2_url']);

            $produto = Produto::create($dados);

            $this->imageService->adicionarImagensProduto($produto, $imagem, $imagem2);

            $produto->load(['categoria:id,nome,icone', 'vendedor:id,nome']);

            return $produto;
        });
    }
}
