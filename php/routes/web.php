<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoriaController;
use App\Http\Controllers\DefinicoesController;
use App\Http\Controllers\EncomendaController;
use App\Http\Controllers\PainelController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProdutoController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\VendedorController;
use App\Http\Controllers\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? (auth()->user()->isSuperAdmin() ? redirect('/super') : redirect('/painel'))
        : redirect('/login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:20,1');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/registar', [\App\Http\Controllers\RegistoController::class, 'show'])->name('registar');
Route::post('/registar', [\App\Http\Controllers\RegistoController::class, 'criar'])->middleware('throttle:10,1');

Route::get('/esqueci-password', [PasswordResetController::class, 'showLinkRequestForm'])->name('password.request');
Route::post('/esqueci-password', [PasswordResetController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1');
Route::get('/repor-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/repor-password/{token}', [PasswordResetController::class, 'reset']);

Route::prefix('painel')->middleware(['auth', 'tenant.context', 'tenant.activo'])->group(function () {
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
    Route::get('/whatsapp/estado', [WhatsAppController::class, 'estado'])->name('whatsapp.estado');

    Route::get('/definicoes', [DefinicoesController::class, 'index'])->name('definicoes.index');
    Route::post('/definicoes', [DefinicoesController::class, 'guardar'])->name('definicoes.guardar');
});

Route::get('/test-waha', function () {
    $url = config('services.waha.url');
    $key = config('services.waha.key');

    $result = ['config_url' => $url, 'config_key' => $key ? substr($key, 0, 6).'...' : 'EMPTY'];

    try {
        $resp = \Illuminate\Support\Facades\Http::withHeaders([
            'X-Api-Key' => $key,
        ])->timeout(30)->get("{$url}/api/sessions");

        $result['http_status'] = $resp->status();
        $result['response'] = $resp->json() ?? $resp->body();
    } catch (\Exception $e) {
        $result['error'] = $e->getMessage();
        $result['error_class'] = get_class($e);
    }

    return response()->json($result);
});

Route::prefix('super')->middleware(['auth', 'super.admin'])->group(function () {
    Route::get('/', [SuperAdminController::class, 'dashboard'])->name('super.dashboard');
    Route::get('/lojas', [SuperAdminController::class, 'lojas'])->name('super.lojas');
    Route::get('/lojas/criar', [SuperAdminController::class, 'criar'])->name('super.lojas.criar');
    Route::post('/lojas/criar-rapido', [SuperAdminController::class, 'criarRapido'])->name('super.lojas.criarRapido');
    Route::get('/lojas/{tenant}', [SuperAdminController::class, 'show'])->name('super.lojas.show');
    Route::post('/lojas/{tenant}/gerar-codigo', [SuperAdminController::class, 'gerarCodigo'])->name('super.lojas.gerarCodigo');
    Route::patch('/lojas/{tenant}/toggle', [SuperAdminController::class, 'toggleActivo'])->name('super.lojas.toggle');
    Route::get('/instancias', function () {
        $instancias = \App\Models\InstanciaWhatsApp::with('tenant')
            ->orderByDesc('updated_at')
            ->paginate(20);
        return view('super.instancias', compact('instancias'));
    })->name('super.instancias');
});
