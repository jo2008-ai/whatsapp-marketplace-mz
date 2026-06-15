<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\InstanciaWhatsApp;
use App\Models\Produto;
use App\Models\Subscricao;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if already seeded (super admin exists)
        if (User::where('email', 'admin@marketplace.co.mz')->exists()) {
            $this->command?->info('Database already seeded. Skipping.');
            return;
        }

        // Super Admin
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@marketplace.co.mz',
            'password' => Hash::make('Admin@2026!'),
            'role' => 'super_admin',
            'tenant_id' => null,
        ]);

        // === TENANT 1: Mercearia Maputo (basic) ===
        $mercearia = Tenant::create([
            'nome_loja' => 'Mercearia Maputo',
            'email_dono' => 'mercearia@teste.com',
            'telefone_dono' => '+258841234567',
            'plano' => 'basic',
            'estado' => 'activo',
            'max_produtos' => 50,
            'max_numeros' => 1,
            'cor_primaria' => '#16A34A',
        ]);

        User::create([
            'tenant_id' => $mercearia->id,
            'name' => 'Mercearia Admin',
            'email' => 'mercearia@teste.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        Subscricao::create([
            'tenant_id' => $mercearia->id,
            'plano' => 'basic',
            'preco_mensal' => 500,
            'data_inicio' => now()->subMonth(),
            'data_fim' => now()->addMonth(),
            'estado' => 'activa',
            'metodo_pagamento' => 'mpesa',
        ]);

        $vendedorM = Vendedor::create([
            'tenant_id' => $mercearia->id,
            'nome' => 'João',
            'numero_whatsapp' => '+258841111111',
            'descricao' => 'Vendedor principal',
        ]);

        $catFrutas = Categoria::create(['tenant_id' => $mercearia->id, 'nome' => 'Frutas', 'icone' => '🍎', 'ordem' => 1]);
        $catLegumes = Categoria::create(['tenant_id' => $mercearia->id, 'nome' => 'Legumes', 'icone' => '🥬', 'ordem' => 2]);
        $catBebidas = Categoria::create(['tenant_id' => $mercearia->id, 'nome' => 'Bebidas', 'icone' => '🥤', 'ordem' => 3]);

        $produtosM = [
            ['nome' => 'Banana (1 cacho)', 'preco' => 50, 'stock' => 30, 'categoria_id' => $catFrutas->id],
            ['nome' => 'Maça (1kg)', 'preco' => 120, 'stock' => 20, 'categoria_id' => $catFrutas->id],
            ['nome' => 'Tomate (1kg)', 'preco' => 80, 'stock' => 25, 'categoria_id' => $catLegumes->id],
            ['nome' => 'Cebola (1kg)', 'preco' => 60, 'stock' => 40, 'categoria_id' => $catLegumes->id],
            ['nome' => 'Coca-Cola 2L', 'preco' => 100, 'stock' => 15, 'categoria_id' => $catBebidas->id],
        ];

        foreach ($produtosM as $p) {
            Produto::create(array_merge($p, [
                'tenant_id' => $mercearia->id,
                'vendedor_id' => $vendedorM->id,
                'disponivel' => true,
            ]));
        }

        InstanciaWhatsApp::create([
            'tenant_id' => $mercearia->id,
            'nome_instancia' => 'default',
            'waha_session' => 'default',
            'estado' => 'desconectada',
        ]);

        // === TENANT 2: Boutique Luxo (pro, trial) ===
        $boutique = Tenant::create([
            'nome_loja' => 'Boutique Luxo',
            'email_dono' => 'boutique@teste.com',
            'telefone_dono' => '+258849876543',
            'plano' => 'pro',
            'estado' => 'trial',
            'trial_termina_em' => now()->addDays(7),
            'max_produtos' => 500,
            'max_numeros' => 3,
            'cor_primaria' => '#9333EA',
        ]);

        User::create([
            'tenant_id' => $boutique->id,
            'name' => 'Boutique Admin',
            'email' => 'boutique@teste.com',
            'password' => Hash::make('123456'),
            'role' => 'admin',
        ]);

        Subscricao::create([
            'tenant_id' => $boutique->id,
            'plano' => 'pro',
            'preco_mensal' => 1500,
            'data_inicio' => now(),
            'data_fim' => now()->addDays(7),
            'estado' => 'activa',
        ]);

        $vendedorB = Vendedor::create([
            'tenant_id' => $boutique->id,
            'nome' => 'Maria',
            'numero_whatsapp' => '+258842222222',
            'descricao' => 'Consultora de moda',
        ]);

        $catSenhoras = Categoria::create(['tenant_id' => $boutique->id, 'nome' => 'Senhoras', 'icone' => '👗', 'ordem' => 1]);
        $catHomens = Categoria::create(['tenant_id' => $boutique->id, 'nome' => 'Homens', 'icone' => '👔', 'ordem' => 2]);
        $catAcessorios = Categoria::create(['tenant_id' => $boutique->id, 'nome' => 'Acessórios', 'icone' => '💍', 'ordem' => 3]);

        $produtosB = [
            ['nome' => 'Vestido Floral', 'preco' => 1500, 'stock' => 10, 'categoria_id' => $catSenhoras->id, 'destaque' => true],
            ['nome' => 'Saia Jeans', 'preco' => 800, 'stock' => 15, 'categoria_id' => $catSenhoras->id],
            ['nome' => 'Camisa Social', 'preco' => 1200, 'stock' => 8, 'categoria_id' => $catHomens->id],
            ['nome' => 'Calça Alfaiataria', 'preco' => 2000, 'stock' => 5, 'categoria_id' => $catHomens->id],
            ['nome' => 'Colar Dourado', 'preco' => 350, 'stock' => 20, 'categoria_id' => $catAcessorios->id],
        ];

        foreach ($produtosB as $p) {
            Produto::create(array_merge($p, [
                'tenant_id' => $boutique->id,
                'vendedor_id' => $vendedorB->id,
                'disponivel' => true,
            ]));
        }

        InstanciaWhatsApp::create([
            'tenant_id' => $boutique->id,
            'nome_instancia' => 'default',
            'waha_session' => 'default',
            'estado' => 'desconectada',
        ]);
    }
}
