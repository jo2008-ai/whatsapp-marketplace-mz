<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiUploadController extends Controller
{
    use ApiResponse;

    public function imagem(Request $request): JsonResponse
    {
        $request->validate([
            'imagem' => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $file = $request->file('imagem');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('public/produtos', $filename);

        $url = url('storage/produtos/' . $filename);

        return $this->success(['url' => $url], 'Imagem enviada.');
    }
}
