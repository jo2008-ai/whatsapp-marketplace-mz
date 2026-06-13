<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
            $table->foreignId('categoria_id')->nullable()->constrained('categorias')->nullOnDelete();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->decimal('preco', 10, 2);
            $table->integer('stock')->default(0);
            $table->string('imagem_url')->nullable();
            $table->boolean('disponivel')->default(true);
            $table->boolean('destaque')->default(false);
            $table->timestamps();

            $table->index(['tenant_id', 'categoria_id']);
            $table->index(['tenant_id', 'disponivel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
