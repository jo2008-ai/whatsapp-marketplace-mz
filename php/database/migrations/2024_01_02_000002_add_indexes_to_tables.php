<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                if (!Schema::hasIndex('produtos', 'idx_produtos_tenant_disp_dest')) {
                    $table->index(['tenant_id', 'disponivel', 'destaque'], 'idx_produtos_tenant_disp_dest');
                }
                if (!Schema::hasIndex('produtos', 'idx_produtos_tenant_nome')) {
                    $table->index(['tenant_id', 'nome'], 'idx_produtos_tenant_nome');
                }
                if (!Schema::hasIndex('produtos', 'idx_produtos_tenant_preco')) {
                    $table->index(['tenant_id', 'preco'], 'idx_produtos_tenant_preco');
                }
            });
        }

        if (Schema::hasTable('encomendas')) {
            Schema::table('encomendas', function (Blueprint $table) {
                if (!Schema::hasIndex('encomendas', 'idx_encomendas_tenant_data')) {
                    $table->index(['tenant_id', 'created_at'], 'idx_encomendas_tenant_data');
                }
                if (!Schema::hasIndex('encomendas', 'idx_encomendas_cliente')) {
                    $table->index(['tenant_id', 'numero_cliente', 'created_at'], 'idx_encomendas_cliente');
                }
            });
        }

        if (Schema::hasTable('sessoes_bot')) {
            Schema::table('sessoes_bot', function (Blueprint $table) {
                if (!Schema::hasIndex('sessoes_bot', 'idx_sessoes_tenant_updated')) {
                    $table->index(['tenant_id', 'updated_at'], 'idx_sessoes_tenant_updated');
                }
            });
        }

        if (Schema::hasTable('logs_bot')) {
            Schema::table('logs_bot', function (Blueprint $table) {
                if (!Schema::hasIndex('logs_bot', 'idx_logs_tenant_num_data')) {
                    $table->index(['tenant_id', 'numero_whatsapp', 'created_at'], 'idx_logs_tenant_num_data');
                }
            });
        }

        if (Schema::hasTable('subscricoes')) {
            Schema::table('subscricoes', function (Blueprint $table) {
                if (!Schema::hasIndex('subscricoes', 'idx_subs_tenant_estado_fim')) {
                    $table->index(['tenant_id', 'estado', 'data_fim'], 'idx_subs_tenant_estado_fim');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('produtos')) {
            Schema::table('produtos', function (Blueprint $table) {
                if (Schema::hasIndex('produtos', 'idx_produtos_tenant_disp_dest')) {
                    $table->dropIndex('idx_produtos_tenant_disp_dest');
                }
                if (Schema::hasIndex('produtos', 'idx_produtos_tenant_nome')) {
                    $table->dropIndex('idx_produtos_tenant_nome');
                }
                if (Schema::hasIndex('produtos', 'idx_produtos_tenant_preco')) {
                    $table->dropIndex('idx_produtos_tenant_preco');
                }
            });
        }

        if (Schema::hasTable('encomendas')) {
            Schema::table('encomendas', function (Blueprint $table) {
                if (Schema::hasIndex('encomendas', 'idx_encomendas_tenant_data')) {
                    $table->dropIndex('idx_encomendas_tenant_data');
                }
                if (Schema::hasIndex('encomendas', 'idx_encomendas_cliente')) {
                    $table->dropIndex('idx_encomendas_cliente');
                }
            });
        }

        if (Schema::hasTable('sessoes_bot')) {
            Schema::table('sessoes_bot', fn(Blueprint $table) => $table->dropIndex('idx_sessoes_tenant_updated'));
        }

        if (Schema::hasTable('logs_bot')) {
            Schema::table('logs_bot', fn(Blueprint $table) => $table->dropIndex('idx_logs_tenant_num_data'));
        }

        if (Schema::hasTable('subscricoes')) {
            Schema::table('subscricoes', fn(Blueprint $table) => $table->dropIndex('idx_subs_tenant_estado_fim'));
        }
    }
};
