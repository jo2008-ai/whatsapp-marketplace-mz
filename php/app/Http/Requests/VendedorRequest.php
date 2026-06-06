<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VendedorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nome' => 'required|string|max:255',
            'numero_whatsapp' => 'required|string|max:20',
            'descricao' => 'nullable|string|max:255',
            'ativo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'nome.required' => 'O nome do vendedor é obrigatório.',
            'nome.max' => 'O nome não pode ter mais de 255 caracteres.',
            'numero_whatsapp.required' => 'O número de WhatsApp é obrigatório.',
            'numero_whatsapp.max' => 'O número não pode ter mais de 20 caracteres.',
        ];
    }
}
