<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProdutoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'nome' => 'required|string|max:100',
            'descricao' => 'nullable|string|max:1000',
            'preco' => 'required|numeric|min:0|max:999999.99',
            'stock' => 'required|integer|min:0',
            'categoria_id' => 'required|integer|exists:categorias,id',
            'vendedor_id' => 'required|integer|exists:vendedores,id',
            'imagem' => ($isCreate ? 'required' : 'nullable') . '|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem2' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'imagem_url' => 'nullable|url|max:2000',
            'imagem2_url' => 'nullable|url|max:2000',
            'disponivel' => 'boolean',
            'destaque' => 'boolean',
            'cores' => 'nullable|array|max:10',
            'cores.*' => 'string|max:30',
            'tamanhos' => 'nullable|array|max:10',
            'tamanhos.*' => 'string|max:10',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do produto é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de 100 caracteres.',
            'preco.required' => 'O preço é obrigatório.',
            'preco.min' => 'O preço não pode ser negativo.',
            'stock.required' => 'O stock é obrigatório.',
            'stock.min' => 'O stock não pode ser negativo.',
            'categoria_id.required' => 'A categoria é obrigatória.',
            'categoria_id.exists' => 'A categoria selecionada não existe.',
            'vendedor_id.required' => 'O vendedor é obrigatório.',
            'vendedor_id.exists' => 'O vendedor selecionado não existe.',
        ];
    }
}
