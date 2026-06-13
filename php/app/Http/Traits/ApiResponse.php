<?php

namespace App\Http\Traits;

use Illuminate\Http\JsonResponse;

trait ApiResponse
{
    protected function success($data = null, string $message = 'Sucesso', int $code = 200): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    protected function error(string $message = 'Erro', int $code = 400, $errors = null): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if ($errors) {
            $response['errors'] = $errors;
        }

        return response()->json($response, $code);
    }

    protected function created($data, string $message = 'Criado com sucesso'): JsonResponse
    {
        return $this->success($data, $message, 201);
    }

    protected function notFound(string $message = 'Não encontrado'): JsonResponse
    {
        return $this->error($message, 404);
    }

    protected function unauthorized(string $message = 'Não autorizado'): JsonResponse
    {
        return $this->error($message, 401);
    }

    protected function forbidden(string $message = 'Acesso negado'): JsonResponse
    {
        return $this->error($message, 403);
    }

    protected function validationError($errors, string $message = 'Dados inválidos'): JsonResponse
    {
        return $this->error($message, 422, $errors);
    }
}
