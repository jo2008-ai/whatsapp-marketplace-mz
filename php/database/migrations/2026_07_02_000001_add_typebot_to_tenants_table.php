<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->boolean('usar_typebot')->default(false);
            $table->string('typebot_bot_id', 255)->nullable();
            $table->string('typebot_api_url', 500)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn(['usar_typebot', 'typebot_bot_id', 'typebot_api_url']);
        });
    }
};
