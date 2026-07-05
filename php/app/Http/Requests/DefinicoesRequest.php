<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DefinicoesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, string> */
    public function rules(): array
    {
        return [
            'nome_loja' => 'required|string|max:255',
            'cor_primaria' => 'nullable|string|max:7',
            'mensagem_boas_vindas' => 'nullable|string|max:500',
            'logo' => 'nullable|image|max:2048',
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'nome_loja.required' => 'O nome da loja é obrigatório.',
            'nome_loja.max' => 'O nome não pode ter mais de 255 caracteres.',
            'cor_primaria.max' => 'A cor deve ter no máximo 7 caracteres (ex: #FF0000).',
            'mensagem_boas_vindas.max' => 'A mensagem de boas-vindas não pode ter mais de 500 caracteres.',
            'logo.image' => 'O ficheiro deve ser uma imagem.',
            'logo.max' => 'A imagem não pode ter mais de 2MB.',
        ];
    }
}
