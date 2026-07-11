<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimentos_stock', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('produto_id')->constrained()->cascadeOnDelete();
            $table->string('tipo', 20); // entrada, saida, ajuste, devolucao
            $table->integer('quantidade');
            $table->integer('stock_anterior');
            $table->integer('stock_actual');
            $table->string('motivo', 255)->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('referencia_tipo', 50)->nullable();
            $table->foreignId('utilizador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'produto_id']);
            $table->index(['tenant_id', 'tipo']);
            $table->index(['produto_id', 'created_at']);
        });

        Schema::table('produtos', function (Blueprint $table) {
            if (!Schema::hasColumn('produtos', 'stock_minimo')) {
                $table->integer('stock_minimo')->default(5)->after('stock');
            }
            if (!Schema::hasColumn('produtos', 'stock_maximo')) {
                $table->integer('stock_maximo')->default(100)->after('stock_minimo');
            }
            if (!Schema::hasColumn('produtos', 'unidade')) {
                $table->string('unidade', 20)->default('unidade')->after('stock_maximo');
            }
            if (!Schema::hasColumn('produtos', 'custo_unitario')) {
                $table->decimal('custo_unitario', 10, 2)->default(0)->after('unidade');
            }
            if (!Schema::hasColumn('produtos', 'alerta_stock_baixo')) {
                $table->boolean('alerta_stock_baixo')->default(true)->after('custo_unitario');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimentos_stock');

        Schema::table('produtos', function (Blueprint $table) {
            $columns = array_filter(
                ['stock_minimo', 'stock_maximo', 'unidade', 'custo_unitario', 'alerta_stock_baixo'],
                fn ($col) => Schema::hasColumn('produtos', $col)
            );
            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};
