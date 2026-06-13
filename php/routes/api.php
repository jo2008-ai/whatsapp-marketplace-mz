<?php

use App\Http\Controllers\BotController;
use App\Http\Controllers\EvolutionWebhookController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiLojaController;
use App\Http\Controllers\Api\ApiProdutoController;
use App\Http\Controllers\Api\ApiCategoriaController;
use App\Http\Controllers\Api\ApiEncomendaController;
use App\Http\Controllers\Api\ApiVendedorController;
use App\Http\Controllers\Api\ApiDefinicoesController;
use App\Http\Controllers\Api\ApiUploadController;
use Illuminate\Support\Facades\Route;

// Webhook do bot (vindo do Python, protegido com HMAC + rate limit)
Route::post('/mensagem', [BotController::class, 'processar'])
    ->middleware(['webhook.verify', 'webhook.rate']);

// Webhook Evolution API (eventos de estado de conexão)
Route::post('/evolution/webhook', [EvolutionWebhookController::class, 'processar']);

// Auth API
Route::post('/auth/login', [ApiAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [ApiAuthController::class, 'logout']);
    Route::get('/auth/me', [ApiAuthController::class, 'me']);
});

// Rotas protegidas da loja
Route::middleware(['auth:sanctum', 'tenant.activo'])->prefix('loja')->group(function () {
    // Dashboard
    Route::get('/dashboard', [ApiLojaController::class, 'dashboard']);

    // Produtos
    Route::get('/produtos', [ApiProdutoController::class, 'index']);
    Route::post('/produtos', [ApiProdutoController::class, 'store']);
    Route::get('/produtos/{id}', [ApiProdutoController::class, 'show']);
    Route::put('/produtos/{id}', [ApiProdutoController::class, 'update']);
    Route::delete('/produtos/{id}', [ApiProdutoController::class, 'destroy']);
    Route::patch('/produtos/{id}/toggle', [ApiProdutoController::class, 'toggleDisponivel']);

    // Categorias
    Route::get('/categorias', [ApiCategoriaController::class, 'index']);
    Route::get('/categorias/{id}', [ApiCategoriaController::class, 'show']);
    Route::post('/categorias', [ApiCategoriaController::class, 'store']);
    Route::put('/categorias/{id}', [ApiCategoriaController::class, 'update']);
    Route::delete('/categorias/{id}', [ApiCategoriaController::class, 'destroy']);

    // Vendedores
    Route::get('/vendedores', [ApiVendedorController::class, 'index']);
    Route::get('/vendedores/{id}', [ApiVendedorController::class, 'show']);
    Route::post('/vendedores', [ApiVendedorController::class, 'store']);
    Route::put('/vendedores/{id}', [ApiVendedorController::class, 'update']);
    Route::delete('/vendedores/{id}', [ApiVendedorController::class, 'destroy']);
    Route::patch('/vendedores/{id}/toggle', [ApiVendedorController::class, 'toggleAtivo']);

    // Encomendas
    Route::get('/encomendas', [ApiEncomendaController::class, 'index']);
    Route::patch('/encomendas/{id}/estado', [ApiEncomendaController::class, 'atualizarEstado']);

    // Definições
    Route::get('/definicoes', [ApiDefinicoesController::class, 'index']);
    Route::post('/definicoes', [ApiDefinicoesController::class, 'guardar']);

    // Upload
    Route::post('/upload/imagem', [ApiUploadController::class, 'imagem']);
});
