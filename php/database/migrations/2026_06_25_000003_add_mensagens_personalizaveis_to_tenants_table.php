<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->text('mensagem_erro_menu')->nullable()->after('mensagem_boas_vindas');
            $table->text('mensagem_categoria_vazia')->nullable()->after('mensagem_erro_menu');
            $table->text('mensagem_pesquisa_vazia')->nullable()->after('mensagem_categoria_vazia');
            $table->text('mensagem_pedido_sucesso')->nullable()->after('mensagem_pesquisa_vazia');
            $table->text('mensagem_pedido_cancelado')->nullable()->after('mensagem_pedido_sucesso');
            $table->text('mensagem_vendedores_indisponivel')->nullable()->after('mensagem_pedido_cancelado');
            $table->text('mensagem_transferencia')->nullable()->after('mensagem_vendedores_indisponivel');
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $table->dropColumn([
                'mensagem_erro_menu',
                'mensagem_categoria_vazia',
                'mensagem_pesquisa_vazia',
                'mensagem_pedido_sucesso',
                'mensagem_pedido_cancelado',
                'mensagem_vendedores_indisponivel',
                'mensagem_transferencia',
            ]);
        });
    }
};
