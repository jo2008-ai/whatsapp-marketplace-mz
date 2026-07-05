<?php

namespace App\Services;

use App\Models\Produto;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    private function getTenantPath(): string
    {
        $tenantId = tenant_id();

        if (is_null($tenantId)) {
            return 'public/produtos';
        }

        return "public/{$tenantId}/produtos";
    }

    private function getTenantUrl(string $filename): string
    {
        $tenantId = tenant_id();

        if (is_null($tenantId)) {
            return url("storage/produtos/{$filename}");
        }

        return url("storage/{$tenantId}/produtos/{$filename}");
    }

    public function adicionarImagensProduto(Produto $produto, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): void
    {
        if ($imagem) {
            $produto->addMedia($imagem)->toMediaCollection('imagens');
        }

        if ($imagem2) {
            $produto->addMedia($imagem2)->toMediaCollection('imagens');
        }
    }

    public function actualizarImagensProduto(Produto $produto, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): void
    {
        if ($imagem) {
            $produto->clearMediaCollection('imagens');
            $produto->addMedia($imagem)->toMediaCollection('imagens');
        }

        if ($imagem2) {
            $produto->clearMediaCollection('imagens');
            $produto->addMedia($imagem2)->toMediaCollection('imagens');
        }
    }

    public function obterImagemProduto(Produto $produto, string $tipo = 'principal'): ?string
    {
        $media = $produto->getMedia('imagens')->first();

        if (!$media) {
            return null;
        }

        return $media->getUrl();
    }

    /** @return array<int, array{id: int, url: string, thumb: string, name: string}> */
    public function obterImagensProduto(Produto $produto): array
    {
        return $produto->getMedia('imagens')->map(fn($media) => [
            'id' => $media->id,
            'url' => $media->getUrl(),
            'thumb' => $media->getUrl('thumb'),
            'name' => $media->name,
        ])->toArray();
    }

    public function eliminarImagensProduto(Produto $produto): void
    {
        $produto->clearMediaCollection('imagens');
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function processarImagens(array $data, ?UploadedFile $imagem = null, ?UploadedFile $imagem2 = null): array
    {
        if ($imagem) {
            $data['imagem_url'] = $this->guardarImagem($imagem);
        }

        if ($imagem2) {
            $data['imagem2_url'] = $this->guardarImagem($imagem2);
        }

        unset($data['imagem'], $data['imagem2']);

        return $data;
    }

    public function guardarImagem(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $this->getTenantPath();

        Storage::makeDirectory($path);

        $file->storeAs($path, $filename);

        return $this->getTenantUrl($filename);
    }
}
