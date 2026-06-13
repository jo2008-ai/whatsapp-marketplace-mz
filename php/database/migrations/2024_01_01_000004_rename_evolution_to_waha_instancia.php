<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('instancias_whatsapp', 'evolution_instance_name')) {
            Schema::table('instancias_whatsapp', function (Blueprint $table) {
                $table->string('waha_session')->default('default')->after('nome_instancia');
            });

            DB::table('instancias_whatsapp')->update(['waha_session' => 'default']);

            Schema::table('instancias_whatsapp', function (Blueprint $table) {
                $table->dropColumn('evolution_instance_name');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instancias_whatsapp', 'waha_session')) {
            Schema::table('instancias_whatsapp', function (Blueprint $table) {
                $table->string('evolution_instance_name')->unique()->after('nome_instancia');
            });

            DB::table('instancias_whatsapp')->update(['evolution_instance_name' => 'default']);

            Schema::table('instancias_whatsapp', function (Blueprint $table) {
                $table->dropColumn('waha_session');
            });
        }
    }
};
