<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('trial_aviso_3d')->default(false)->after('trial_termina_em');
            $table->boolean('trial_aviso_1d')->default(false)->after('trial_aviso_3d');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['trial_aviso_3d', 'trial_aviso_1d']);
        });
    }
};
