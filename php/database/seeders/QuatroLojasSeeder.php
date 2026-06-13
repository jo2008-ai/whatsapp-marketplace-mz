<?php

namespace Database\Seeders;

use App\Models\Categoria;
use App\Models\InstanciaWhatsApp;
use App\Models\Produto;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Vendedor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class QuatroLojasSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@plataforma.com',
            'password' => Hash::make('admin123'),
            'role' => 'super_admin',
            'tenant_id' => null,
        ]);

        $lojas = [
            [
                'nome_loja' => 'Loja Tech',
                'instancia_whatsapp' => 'loja_tech',
                'cor_primaria' => '#2563EB',
                'email_dono' => 'tech@lojas.mz',
                'telefone_dono' => '+258841000001',
                'vendedor_nome' => 'Carlos',
                'vendedor_numero' => '+258841000011',
                'categorias' => [
                    ['nome' => 'Telemóveis', 'icone' => '📱', 'ordem' => 1],
                    ['nome' => 'Acessórios', 'icone' => '🎧', 'ordem' => 2],
                    ['nome' => 'Computadores', 'icone' => '💻', 'ordem' => 3],
                ],
                'produtos' => [
                    ['nome' => 'Samsung Galaxy A14', 'preco' => 8500, 'stock' => 15, 'cat_idx' => 0],
                    ['nome' => 'iPhone 13 128GB', 'preco' => 32000, 'stock' => 5, 'cat_idx' => 0],
                    ['nome' => 'AirPods Pro', 'preco' => 4500, 'stock' => 20, 'cat_idx' => 1],
                    ['nome' => 'Capa Silicone Universal', 'preco' => 350, 'stock' => 50, 'cat_idx' => 1],
                    ['nome' => 'Lenovo IdeaPad 3', 'preco' => 18000, 'stock' => 8, 'cat_idx' => 2],
                ],
            ],
            [
                'nome_loja' => 'Loja Fashion',
                'instancia_whatsapp' => 'loja_fashion',
                'cor_primaria' => '#9333EA',
                'email_dono' => 'fashion@lojas.mz',
                'telefone_dono' => '+258842000002',
                'vendedor_nome' => 'Maria',
                'vendedor_numero' => '+258842000022',
                'categorias' => [
                    ['nome' => 'Senhoras', 'icone' => '👗', 'ordem' => 1],
                    ['nome' => 'Homens', 'icone' => '👔', 'ordem' => 2],
                    ['nome' => 'Acessórios', 'icone' => '💍', 'ordem' => 3],
                ],
                'produtos' => [
                    ['nome' => 'Vestido Floral', 'preco' => 1500, 'stock' => 10, 'cat_idx' => 0],
                    ['nome' => 'Saia Jeans', 'preco' => 800, 'stock' => 15, 'cat_idx' => 0],
                    ['nome' => 'Camisa Social', 'preco' => 1200, 'stock' => 8, 'cat_idx' => 1],
                    ['nome' => 'Calça Alfaiataria', 'preco' => 2000, 'stock' => 5, 'cat_idx' => 1],
                    ['nome' => 'Colar Dourado', 'preco' => 350, 'stock' => 20, 'cat_idx' => 2],
                ],
            ],
            [
                'nome_loja' => 'Loja Mercado',
                'instancia_whatsapp' => 'loja_mercado',
                'cor_primaria' => '#16A34A',
                'email_dono' => 'mercado@lojas.mz',
                'telefone_dono' => '+258843000003',
                'vendedor_nome' => 'João',
                'vendedor_numero' => '+258843000033',
                'categorias' => [
                    ['nome' => 'Frutas', 'icone' => '🍎', 'ordem' => 1],
                    ['nome' => 'Legumes', 'icone' => '🥬', 'ordem' => 2],
                    ['nome' => 'Bebidas', 'icone' => '🥤', 'ordem' => 3],
                ],
                'produtos' => [
                    ['nome' => 'Banana (1 cacho)', 'preco' => 50, 'stock' => 30, 'cat_idx' => 0],
                    ['nome' => 'Maçã (1kg)', 'preco' => 120, 'stock' => 20, 'cat_idx' => 0],
                    ['nome' => 'Tomate (1kg)', 'preco' => 80, 'stock' => 25, 'cat_idx' => 1],
                    ['nome' => 'Cebola (1kg)', 'preco' => 60, 'stock' => 40, 'cat_idx' => 1],
                    ['nome' => 'Coca-Cola 2L', 'preco' => 100, 'stock' => 15, 'cat_idx' => 2],
                ],
            ],
            [
                'nome_loja' => 'Loja Extra',
                'instancia_whatsapp' => 'loja_extra',
                'cor_primaria' => '#EA580C',
                'email_dono' => 'extra@lojas.mz',
                'telefone_dono' => '+258844000004',
                'vendedor_nome' => 'Ana',
                'vendedor_numero' => '+258844000044',
                'categorias' => [
                    ['nome' => 'Casa', 'icone' => '🏠', 'ordem' => 1],
                    ['nome' => 'Cozinha', 'icone' => '🍳', 'ordem' => 2],
                    ['nome' => 'Jardim', 'icone' => '🌿', 'ordem' => 3],
                ],
                'produtos' => [
                    ['nome' => 'Kit Panelas 5 Peças', 'preco' => 3500, 'stock' => 10, 'cat_idx' => 1],
                    ['nome' => 'Toalha de Mesa', 'preco' => 450, 'stock' => 25, 'cat_idx' => 0],
                    ['nome' => 'Vaso de Cerâmica', 'preco' => 280, 'stock' => 30, 'cat_idx' => 2],
                    ['nome' => 'Conjunto de Toalhas', 'preco' => 1200, 'stock' => 12, 'cat_idx' => 0],
                    ['nome' => 'Regador 5L', 'preco' => 180, 'stock' => 40, 'cat_idx' => 2],
                ],
            ],
        ];

        foreach ($lojas as $dados) {
            $tenant = Tenant::create([
                'nome_loja' => $dados['nome_loja'],
                'email_dono' => $dados['email_dono'],
                'telefone_dono' => $dados['telefone_dono'],
                'instancia_whatsapp' => $dados['instancia_whatsapp'],
                'activo' => true,
                'cor_primaria' => $dados['cor_primaria'],
            ]);

            User::create([
                'tenant_id' => $tenant->id,
                'name' => $dados['nome_loja'] . ' Admin',
                'email' => $dados['email_dono'],
                'password' => Hash::make('123456'),
                'role' => 'admin',
            ]);

            $vendedor = Vendedor::create([
                'tenant_id' => $tenant->id,
                'nome' => $dados['vendedor_nome'],
                'numero_whatsapp' => $dados['vendedor_numero'],
            ]);

            $categorias = [];
            foreach ($dados['categorias'] as $cat) {
                $categorias[] = Categoria::create([
                    'tenant_id' => $tenant->id,
                    'nome' => $cat['nome'],
                    'icone' => $cat['icone'],
                    'ordem' => $cat['ordem'],
                ]);
            }

            foreach ($dados['produtos'] as $prod) {
                Produto::create([
                    'tenant_id' => $tenant->id,
                    'vendedor_id' => $vendedor->id,
                    'categoria_id' => $categorias[$prod['cat_idx']]->id,
                    'nome' => $prod['nome'],
                    'preco' => $prod['preco'],
                    'stock' => $prod['stock'],
                    'disponivel' => true,
                ]);
            }

            InstanciaWhatsApp::create([
                'tenant_id' => $tenant->id,
                'nome_instancia' => 'default',
                'waha_session' => 'default',
                'estado' => 'desconectada',
            ]);
        }
    }
}
