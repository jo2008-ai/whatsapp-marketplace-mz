<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sessoes_bot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('numero_whatsapp');
            $table->string('estado')->default('inicio');
            $table->json('dados')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique(['tenant_id', 'numero_whatsapp']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sessoes_bot');
    }
};
