<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('encomendas', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('categorias', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('vendedores', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('produtos', fn(Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('encomendas', fn(Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('categorias', fn(Blueprint $table) => $table->dropSoftDeletes());
        Schema::table('vendedores', fn(Blueprint $table) => $table->dropSoftDeletes());
    }
};
