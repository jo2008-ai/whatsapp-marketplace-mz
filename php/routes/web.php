<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DefinicoesController;
use App\Http\Controllers\EncomendaController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\RegistoController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

// Público
Route::get('/', function () {
    return auth()->check()
        ? (auth()->user()->isSuperAdmin() ? redirect('/super') : redirect('/painel'))
        : view('publico.landing');
});

// Autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Password reset
Route::get('/esqueci-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/esqueci-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1');
Route::get('/repor-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/repor-password/{token}', [PasswordResetController::class, 'reset']);

// Registo de nova loja
Route::get('/registar', [RegistoController::class, 'show']);
Route::post('/registar', [RegistoController::class, 'criar'])->middleware('throttle:3,1');

// Painel da loja (requer login + tenant activo)
Route::prefix('painel')->middleware(['auth', 'tenant.activo'])->group(function () {
    Route::get('/', [PainelController::class, 'dashboard'])->name('painel.dashboard');

    Route::resource('produtos', ProdutoController::class)->except(['show']);

    Route::get('/categorias', [CategoriaController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaController::class, 'store'])->name('categorias.store');
    Route::put('/categorias/{categoria}', [CategoriaController::class, 'update'])->name('categorias.update');
    Route::delete('/categorias/{categoria}', [CategoriaController::class, 'destroy'])->name('categorias.destroy');

    Route::get('/vendedores', [VendedorController::class, 'index'])->name('vendedores.index');
    Route::post('/vendedores', [VendedorController::class, 'store'])->name('vendedores.store');
    Route::put('/vendedores/{vendedor}', [VendedorController::class, 'update'])->name('vendedores.update');
    Route::delete('/vendedores/{vendedor}', [VendedorController::class, 'destroy'])->name('vendedores.destroy');

    Route::get('/encomendas', [EncomendaController::class, 'index'])->name('encomendas.index');
    Route::patch('/encomendas/{encomenda}/estado', [EncomendaController::class, 'atualizarEstado'])->name('encomendas.estado');

    Route::get('/whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
    Route::post('/whatsapp/conectar', [WhatsAppController::class, 'conectar'])->name('whatsapp.conectar');
    Route::get('/whatsapp/qr', [WhatsAppController::class, 'qr'])->name('whatsapp.qr');

    Route::get('/definicoes', [DefinicoesController::class, 'index'])->name('definicoes.index');
    Route::post('/definicoes', [DefinicoesController::class, 'guardar'])->name('definicoes.guardar');

    Route::get('/plano', [\App\Http\Controllers\PlanoController::class, 'index'])->name('plano.index');
    Route::post('/plano/upgrade', [\App\Http\Controllers\PlanoController::class, 'upgrade'])->name('plano.upgrade');
});

// Super Admin
Route::prefix('super')->middleware(['auth', 'super.admin'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('super.dashboard');
    Route::get('/lojas', [SuperAdminController::class, 'lojas'])->name('super.lojas');
    Route::get('/lojas/criar', [SuperAdminController::class, 'criar'])->name('super.lojas.criar');
    Route::post('/lojas/guardar', [SuperAdminController::class, 'guardar'])->name('super.lojas.guardar');
    Route::get('/lojas/{tenant}', [SuperAdminController::class, 'detalhe'])->name('super.lojas.detalhe');
    Route::patch('/lojas/{tenant}/estado', [SuperAdminController::class, 'alterarEstado'])->name('super.lojas.estado');
    Route::post('/lojas/{tenant}/subscricao', [SuperAdminController::class, 'renovarSubscricao'])->name('super.lojas.subscricao');
    Route::get('/receita', [SuperAdminController::class, 'receita'])->name('super.receita');
    Route::get('/instancias', [SuperAdminController::class, 'instancias'])->name('super.instancias');
});
