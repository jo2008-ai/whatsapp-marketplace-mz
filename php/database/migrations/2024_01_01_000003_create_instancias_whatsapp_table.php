<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('instancias_whatsapp')) {
            Schema::create('instancias_whatsapp', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('numero_whatsapp')->nullable();
                $table->string('nome_instancia');
                $table->string('evolution_instance_name')->unique();
                $table->enum('estado', ['conectada', 'desconectada', 'aguarda_qr'])->default('desconectada');
                $table->text('qr_code_url')->nullable();
                $table->timestamp('conectada_em')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instancias_whatsapp');
    }
};
