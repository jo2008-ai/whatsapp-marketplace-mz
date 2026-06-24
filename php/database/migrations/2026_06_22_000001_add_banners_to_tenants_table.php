<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Banner de promoção (controlado pelo dono da loja)
            $table->boolean('banner_promo_activo')->default(false);
            $table->string('banner_promo_titulo', 100)->nullable();
            $table->string('banner_promo_texto', 255)->nullable();
            $table->string('banner_promo_cor', 7)->default('#FF6B35');
            $table->timestamp('banner_promo_expira_em')->nullable();

            // Banner global (controlado pelo admin SaaS)
            $table->boolean('banner_global_activo')->default(false);
            $table->string('banner_global_titulo', 100)->nullable();
            $table->string('banner_global_texto', 255)->nullable();
            $table->string('banner_global_cor', 7)->default('#2563EB');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'banner_promo_activo',
                'banner_promo_titulo',
                'banner_promo_texto',
                'banner_promo_cor',
                'banner_promo_expira_em',
                'banner_global_activo',
                'banner_global_titulo',
                'banner_global_texto',
                'banner_global_cor',
            ]);
        });
    }
};
