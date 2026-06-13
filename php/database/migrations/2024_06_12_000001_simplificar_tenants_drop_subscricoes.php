<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscricoes');

        Schema::table('tenants', function (Blueprint $table) {
            $table->string('instancia_whatsapp')->nullable()->after('telefone_dono');
            $table->boolean('activo')->default(true)->after('mensagem_boas_vindas');
            $table->dropColumn([
                'plano', 'estado', 'trial_termina_em',
                'max_produtos', 'max_numeros',
                'trial_aviso_3d', 'trial_aviso_1d',
            ]);
        });

        DB::table('tenants')->where('activo', false)->update(['activo' => true]);
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->enum('plano', ['basic', 'pro', 'enterprise'])->default('basic')->after('telefone_dono');
            $table->enum('estado', ['activo', 'suspenso', 'cancelado', 'trial'])->default('activo')->after('plano');
            $table->timestamp('trial_termina_em')->nullable()->after('estado');
            $table->integer('max_produtos')->default(99999)->after('trial_termina_em');
            $table->integer('max_numeros')->default(99999)->after('max_produtos');
            $table->dropColumn(['instancia_whatsapp', 'activo']);
        });

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
};
