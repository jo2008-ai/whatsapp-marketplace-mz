<?php

use App\Http\Controllers\BotController;
use App\Http\Controllers\WahaWebhookController;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiLojaController;
use App\Http\Controllers\Api\ApiProdutoController;
use App\Http\Controllers\Api\ApiCategoriaController;
use App\Http\Controllers\Api\ApiEncomendaController;
use App\Http\Controllers\Api\ApiVendedorController;
use App\Http\Controllers\Api\ApiDefinicoesController;
use App\Http\Controllers\Api\ApiUploadController;
use App\Http\Controllers\Api\AdminLojaController;
use App\Http\Controllers\Api\ApiPainelController;
use Illuminate\Support\Facades\Route;

// Webhook do bot (vindo do Python, protegido com HMAC + rate limit)
Route::post('/mensagem', [BotController::class, 'processar'])
    ->middleware(['webhook.verify', 'webhook.rate']);

// Webhook Evolution API (eventos de estado de conexão)
Route::post('/waha/webhook', [WahaWebhookController::class, 'processar'])
    ->middleware(['webhook.verify', 'webhook.rate']);

// Auth API
Route::post('/auth/login', [ApiAuthController::class, 'login']);
Route::post('/auth/login-by-code', [ApiAuthController::class, 'loginByCode']);
Route::post('/auth/register', [ApiAuthController::class, 'register']);

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
    Route::post('/definicoes/banner-promo', [ApiDefinicoesController::class, 'bannerPromo']);

    // Upload
    Route::post('/upload/imagem', [ApiUploadController::class, 'imagem']);
});

// Rotas admin — protegidas por chave secreta
Route::middleware('admin.key')->prefix('admin')->group(function () {
    Route::post('/lojas', [AdminLojaController::class, 'criar']);
    Route::get('/lojas', [AdminLojaController::class, 'listar']);
    Route::delete('/lojas/{id}', [AdminLojaController::class, 'eliminar']);
    Route::delete('/lojas', [AdminLojaController::class, 'eliminarTodas']);
    Route::post('/lojas/{id}/instancia', [AdminLojaController::class, 'criarInstancia']);
    Route::post('/banner-global', [AdminLojaController::class, 'bannerGlobal']);
});

// Rotas do painel Python (acesso interno)
Route::get('/painel/lojas', [ApiPainelController::class, 'listarLojas']);
Route::post('/painel/registrar', [ApiPainelController::class, 'registrar']);
