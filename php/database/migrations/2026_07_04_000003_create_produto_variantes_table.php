<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_variantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 100)->nullable();
            $table->decimal('preco_override', 10, 2)->nullable();
            $table->unsignedInteger('stock')->default(0);
            $table->boolean('disponivel')->default(true);
            $table->string('imagem_url', 500)->nullable();
            $table->json('atributos');
            $table->timestamps();

            $table->index(['produto_id', 'disponivel']);
            $table->index('sku');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_variantes');
    }
};
