<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CategoriaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
            'icone' => 'nullable|string|max:10',
            'ordem' => 'nullable|integer|min:0',
            'ativo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome da categoria é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',
            'icone.max' => 'O ícone não pode ter mais de 10 caracteres.',
            'ordem.min' => 'A ordem deve ser um número positivo.',
        ];
    }
}
