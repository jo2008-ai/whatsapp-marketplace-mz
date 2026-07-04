<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('atributo_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('atributo_id')->constrained()->cascadeOnDelete();
            $table->string('codigo', 50);
            $table->string('nome', 100);
            $table->string('valor', 100)->nullable();
            $table->string('swatch_url', 500)->nullable();
            $table->unsignedInteger('ordem')->default(0);
            $table->timestamps();

            $table->unique(['atributo_id', 'codigo']);
            $table->index('atributo_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('atributo_valores');
    }
};
