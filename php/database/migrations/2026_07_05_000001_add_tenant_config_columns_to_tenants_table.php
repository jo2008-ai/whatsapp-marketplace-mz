<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->string('timezone', 50)->default('Africa/Maputo')->after('cor_primaria');
            $table->string('moeda', 3)->default('MZN')->after('timezone');
            $table->string('idioma', 5)->default('pt')->after('moeda');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['timezone', 'moeda', 'idioma']);
        });
    }
};
