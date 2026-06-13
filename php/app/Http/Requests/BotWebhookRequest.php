<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BotWebhookRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => 'required|integer|exists:tenants,id',
            'instance_name' => 'nullable|string|max:255',
            'numero' => 'required|string|max:20',
            'mensagem' => 'required|string|max:1000',
            'nome' => 'nullable|string|max:255',
            'is_grupo' => 'nullable|boolean',
            'grupo_id' => 'nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'O tenant_id é obrigatório.',
            'tenant_id.exists' => 'Tenant inválido.',
            'numero.required' => 'O número de telefone é obrigatório.',
            'mensagem.required' => 'A mensagem é obrigatória.',
            'mensagem.max' => 'A mensagem não pode ter mais de 1000 caracteres.',
        ];
    }
}
