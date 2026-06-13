<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                if (!Schema::hasColumn('produtos', 'cores')) {
                    $table->json('cores')->nullable()->after('imagem2_url');
                }
                if (!Schema::hasColumn('produtos', 'tamanhos')) {
                    $table->json('tamanhos')->nullable()->after('cores');
                }
            });
        }

        if (Schema::hasTable('encomendas')) {
            Schema::table('encomendas', function (Blueprint $table) {
                if (!Schema::hasColumn('encomendas', 'cor_escolhida')) {
                    $table->string('cor_escolhida', 50)->nullable()->after('produto_id');
                }
                if (!Schema::hasColumn('encomendas', 'tamanho_escolhido')) {
                    $table->string('tamanho_escolhido', 10)->nullable()->after('cor_escolhida');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('encomendas')) {
            Schema::table('encomendas', function (Blueprint $table) {
                $columns = array_filter(['cor_escolhida', 'tamanho_escolhido'], fn($col) => Schema::hasColumn('encomendas', $col));
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                $columns = array_filter(['cores', 'tamanhos'], fn($col) => Schema::hasColumn('produtos', $col));
                if (!empty($columns)) {
                    $table->dropColumn($columns);
                }
            });
        }
    }
};
