<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscricoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->enum('plano', ['basic', 'pro', 'enterprise']);
            $table->decimal('preco_mensal', 10, 2);
            $table->date('data_inicio');
            $table->date('data_fim');
            $table->enum('estado', ['activa', 'expirada', 'cancelada'])->default('activa');
            $table->enum('metodo_pagamento', ['mpesa', 'transferencia', 'manual'])->nullable();
            $table->string('referencia_pagamento')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscricoes');
    }
};
