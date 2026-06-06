<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Produtos — índices para filtros rápidos
        Schema::table('produtos', function (Blueprint $table) {
            $table->index(['tenant_id', 'disponivel', 'destaque'], 'idx_produtos_tenant_disp_dest');
            $table->index(['tenant_id', 'nome'], 'idx_produtos_tenant_nome');
            $table->index(['tenant_id', 'preco'], 'idx_produtos_tenant_preco');
        });

        // Encomendas — índices para dashboard e filtros
        Schema::table('encomendas', function (Blueprint $table) {
            $table->index(['tenant_id', 'created_at'], 'idx_encomendas_tenant_data');
            $table->index(['tenant_id', 'numero_cliente', 'created_at'], 'idx_encomendas_cliente');
        });

        // Sessões bot — lookup rápido
        Schema::table('sessoes_bot', function (Blueprint $table) {
            $table->index(['tenant_id', 'updated_at'], 'idx_sessoes_tenant_updated');
        });

        // Logs bot — queries de auditoria
        Schema::table('logs_bot', function (Blueprint $table) {
            $table->index(['tenant_id', 'numero_whatsapp', 'created_at'], 'idx_logs_tenant_num_data');
        });

        // Subscrições
        Schema::table('subscricoes', function (Blueprint $table) {
            $table->index(['tenant_id', 'estado', 'data_fim'], 'idx_subs_tenant_estado_fim');
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropIndex('idx_produtos_tenant_disp_dest');
            $table->dropIndex('idx_produtos_tenant_nome');
            $table->dropIndex('idx_produtos_tenant_preco');
        });
        Schema::table('encomendas', function (Blueprint $table) {
            $table->dropIndex('idx_encomendas_tenant_data');
            $table->dropIndex('idx_encomendas_cliente');
        });
        Schema::table('sessoes_bot', fn(Blueprint $table) => $table->dropIndex('idx_sessoes_tenant_updated'));
        Schema::table('logs_bot', fn(Blueprint $table) => $table->dropIndex('idx_logs_tenant_num_data'));
        Schema::table('subscricoes', fn(Blueprint $table) => $table->dropIndex('idx_subs_tenant_estado_fim'));
    }
};
