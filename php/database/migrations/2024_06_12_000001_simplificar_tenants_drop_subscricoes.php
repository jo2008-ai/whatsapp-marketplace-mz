<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscricoes');

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'instancia_whatsapp')) {
                $table->string('instancia_whatsapp')->nullable()->after('telefone_dono');
            }
            if (!Schema::hasColumn('tenants', 'activo')) {
                $table->boolean('activo')->default(true)->after('mensagem_boas_vindas');
            }
        });

        if (Schema::hasColumn('tenants', 'activo')) {
            DB::table('tenants')->where('activo', false)->update(['activo' => true]);
        }
    }

    public function down(): void {}
};
