<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instancias_whatsapp', function (Blueprint $table) {
            $table->string('waha_url')->nullable()->after('waha_session');
        });
    }

    public function down(): void
    {
        Schema::table('instancias_whatsapp', function (Blueprint $table) {
            $table->dropColumn('waha_url');
        });
    }
};
