<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class ImageService
{
    private const STORAGE_PATH = 'public/produtos';

    public function guardarImagem(UploadedFile $file): string
    {
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs(self::STORAGE_PATH, $filename);
        return url('storage/produtos/' . $filename);
    }

    public function processarImagens(array $data, UploadedFile $imagem = null, UploadedFile $imagem2 = null): array
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
}
