<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        $gateways = [
            [
                'name'               => 'PIX / Transferência',
                'slug'               => 'pix',
                'driver'             => 'pix',
                'active'             => true,
                'test_mode'          => false,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0,
                'sort_order'         => 1,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => false,
                'supports_refund'    => false,
            ],
            [
                'name'               => 'Boleto Bancário',
                'slug'               => 'boleto',
                'driver'             => 'boleto',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 3.50,
                'fee_percentage'     => 0,
                'sort_order'         => 2,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => false,
                'supports_refund'    => false,
            ],
            [
                'name'               => 'Cartão de Crédito',
                'slug'               => 'credit_card',
                'driver'             => 'stripe',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0.50,
                'fee_percentage'     => 2.9,
                'sort_order'         => 3,
                'allowed_currencies' => ['BRL', 'USD'],
                'supports_recurring' => true,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'PayPal',
                'slug'               => 'paypal',
                'driver'             => 'paypal',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0.30,
                'fee_percentage'     => 3.4,
                'sort_order'         => 4,
                'allowed_currencies' => ['USD', 'BRL'],
                'supports_recurring' => true,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'Mercado Pago',
                'slug'               => 'mercadopago',
                'driver'             => 'mercadopago',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0,
                'fee_percentage'     => 4.99,
                'sort_order'         => 5,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => false,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'PagHiper (PIX + Boleto)',
                'slug'               => 'paghiper',
                'driver'             => 'paghiper',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0.0199,
                'sort_order'         => 6,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => true,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'Efí (PIX)',
                'slug'               => 'efirpro',
                'driver'             => 'efirpro',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0.0099,
                'sort_order'         => 7,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => true,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'Banco Inter (PIX)',
                'slug'               => 'bancointer',
                'driver'             => 'bancointer',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0,
                'sort_order'         => 8,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => false,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'Banco do Brasil (PIX)',
                'slug'               => 'bancobrasil',
                'driver'             => 'bancobrasil',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0,
                'sort_order'         => 9,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => false,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'PagBank',
                'slug'               => 'pagbank',
                'driver'             => 'pagbank',
                'active'             => false,
                'test_mode'          => true,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0.0299,
                'sort_order'         => 10,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => true,
                'supports_refund'    => true,
            ],
            [
                'name'               => 'Saldo de Crédito',
                'slug'               => 'credit_balance',
                'driver'             => 'credit',
                'active'             => true,
                'test_mode'          => false,
                'fee_fixed'          => 0,
                'fee_percentage'     => 0,
                'sort_order'         => 20,
                'allowed_currencies' => ['BRL'],
                'supports_recurring' => false,
                'supports_refund'    => false,
            ],
        ];

        foreach ($gateways as $gw) {
            Gateway::firstOrCreate(['slug' => $gw['slug']], $gw);
        }

        $this->command->info('Gateways seeded.');
    }
}
