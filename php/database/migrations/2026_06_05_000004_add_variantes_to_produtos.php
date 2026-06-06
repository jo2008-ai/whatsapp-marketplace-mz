<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->json('cores')->nullable()->after('imagem2_url');
            $table->json('tamanhos')->nullable()->after('cores');
        });

        Schema::table('encomendas', function (Blueprint $table) {
            $table->string('cor_escolhida', 50)->nullable()->after('produto_id');
            $table->string('tamanho_escolhido', 10)->nullable()->after('cor_escolhida');
        });
    }

    public function down(): void
    {
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropColumn(['cor_escolhida', 'tamanho_escolhido']);
        });

        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn(['cores', 'tamanhos']);
        });
    }
};
