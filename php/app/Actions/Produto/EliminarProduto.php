<?php

namespace App\Actions\Produto;

use App\Models\Produto;
use App\Models\Tenant;
use App\Services\ImageService;
use Illuminate\Support\Facades\DB;

class EliminarProduto
{
    public function __construct(
        private ImageService $imageService
    ) {}

    public function handle(Tenant $tenant, int $id): bool
    {
        $produto = Produto::find($id);

        if (!$produto) {
            return false;
        }

        return DB::transaction(function () use ($produto) {
            $this->imageService->eliminarImagensProduto($produto);

            $produto->delete();

            return true;
        });
    }
}
