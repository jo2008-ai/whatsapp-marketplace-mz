<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'trial_aviso_3d')) {
                $table->boolean('trial_aviso_3d')->default(false)->after('trial_termina_em');
            }
            if (!Schema::hasColumn('tenants', 'trial_aviso_1d')) {
                $table->boolean('trial_aviso_1d')->default(false)->after('trial_aviso_3d');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (Schema::hasColumn('tenants', 'trial_aviso_3d')) {
                $table->dropColumn('trial_aviso_3d');
            }
            if (Schema::hasColumn('tenants', 'trial_aviso_1d')) {
                $table->dropColumn('trial_aviso_1d');
            }
        });
    }
};
