<?php

namespace App\Actions\Produto;

use App\Models\Produto;
use App\Models\Tenant;
use App\Services\ImageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ActualizarProduto
{
    public function __construct(
        private ImageService $imageService
    ) {}

    public function handle(Tenant $tenant, int $id, array $dados, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): ?Produto
    {
        $produto = Produto::with(['categoria:id,nome,icone', 'vendedor:id,nome'])->find($id);

        if (!$produto) {
            return null;
        }

        return DB::transaction(function () use ($produto, $dados, $imagem, $imagem2) {
            unset($dados['imagem'], $dados['imagem2'], $dados['imagem_url'], $dados['imagem2_url']);

            $produto->update($dados);

            $this->imageService->actualizarImagensProduto($produto, $imagem, $imagem2);

            $produto->load(['categoria:id,nome,icone', 'vendedor:id,nome']);

            return $produto;
        });
    }
}
