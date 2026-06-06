<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('nome_loja');
            $table->string('email_dono');
            $table->string('telefone_dono')->nullable();
            $table->enum('plano', ['basic', 'pro', 'enterprise'])->default('basic');
            $table->enum('estado', ['activo', 'suspenso', 'cancelado', 'trial'])->default('trial');
            $table->timestamp('trial_termina_em')->nullable();
            $table->integer('max_produtos')->default(50);
            $table->integer('max_numeros')->default(1);
            $table->string('logo_url')->nullable();
            $table->string('cor_primaria', 7)->default('#2563EB');
            $table->text('mensagem_boas_vindas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
