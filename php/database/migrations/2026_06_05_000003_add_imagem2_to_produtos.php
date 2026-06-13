<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('produtos') && !Schema::hasColumn('produtos', 'imagem2_url')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->string('imagem2_url')->nullable()->after('imagem_url');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('produtos') && Schema::hasColumn('produtos', 'imagem2_url')) {
            Schema::table('produtos', function (Blueprint $table) {
                $table->dropColumn('imagem2_url');
            });
        }
    }
};
