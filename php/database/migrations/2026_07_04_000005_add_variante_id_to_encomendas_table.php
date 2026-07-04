<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('encomendas', function (Blueprint $table) {
            if (!Schema::hasColumn('encomendas', 'variante_id')) {
                $table->foreignId('variante_id')->nullable()->after('produto_id')->constrained('produto_variantes')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('encomendas', function (Blueprint $table) {
            if (Schema::hasColumn('encomendas', 'variante_id')) {
                $table->dropForeign(['variante_id']);
                $table->dropColumn('variante_id');
            }
        });
    }
};
