<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('encomendas')) {
            Schema::create('encomendas', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('numero_cliente');
                $table->string('nome_cliente')->nullable();
                $table->foreignId('produto_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vendedor_id')->nullable()->constrained('vendedores')->nullOnDelete();
                $table->integer('quantidade')->default(1);
                $table->decimal('preco_total', 10, 2);
                $table->enum('estado', ['pendente', 'confirmada', 'entregue', 'cancelada'])->default('pendente');
                $table->text('observacoes')->nullable();
                $table->timestamps();

                $table->index(['tenant_id', 'estado']);
                $table->index(['tenant_id', 'numero_cliente']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('encomendas');
    }
};
