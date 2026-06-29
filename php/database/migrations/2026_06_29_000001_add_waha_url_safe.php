<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('instancias_whatsapp', 'waha_url')) {
            Schema::table('instancias_whatsapp', function (Blueprint $table) {
                $table->string('waha_url')->nullable()->after('waha_session');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('instancias_whatsapp', 'waha_url')) {
            Schema::table('instancias_whatsapp', function (Blueprint $table) {
                $table->dropColumn('waha_url');
            });
        }
    }
};
