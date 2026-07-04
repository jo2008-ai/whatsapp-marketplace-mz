<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 50);
            $table->string('nome', 100);
            $table->enum('tipo', ['cor', 'tamanho', 'material', 'peso', 'custom']);
            $table->boolean('is_filterable')->default(true);
            $table->boolean('is_configurable')->default(true);
            $table->string('swatch_type', 20)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['tenant_id', 'codigo']);
            $table->index(['tenant_id', 'is_filterable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atributos');
    }
};
