<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('POST');

        return [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string',
            'preco' => 'required|numeric|min:0.01',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'nullable|exists:categorias,id',
            'vendedor_id' => 'nullable|exists:vendedores,id',
            'imagem' => ($isCreate ? 'required' : 'nullable') . '|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
            'cores' => 'nullable|array|max:10',
            'cores.*' => 'string|max:30',
            'tamanhos' => 'nullable|array|max:10',
            'tamanhos.*' => 'string|max:10',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do produto é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',
            'preco.required' => 'O preço é obrigatório.',
            'preco.min' => 'O preço deve ser no mínimo 0.01.',
            'stock.required' => 'O stock é obrigatório.',
            'stock.min' => 'O stock não pode ser negativo.',
            'imagem.required' => 'A imagem do produto é obrigatória.',
            'imagem.image' => 'O ficheiro deve ser uma imagem.',
            'imagem.mimes' => 'A imagem deve ser JPG, JPEG, PNG ou WebP.',
            'imagem.max' => 'A imagem não pode ter mais de 2MB.',
            'categoria_id.exists' => 'A categoria selecionada não existe.',
            'vendedor_id.exists' => 'O vendedor selecionado não existe.',
        ];
    }
}
