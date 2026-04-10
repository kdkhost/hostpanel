<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Cria gateways padrão se não existirem
        $defaultGateways = [
            [
                'name' => 'PagHiper',
                'slug' => 'paghiper',
                'driver' => 'paghiper',
                'active' => false,
                'test_mode' => true,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.0000,
                'sort_order' => 1,
                'supports_recurring' => false,
                'supports_refund' => true,
                'allowed_currencies' => ['BRL'],
                'due_days' => 3,
            ],
            [
                'name' => 'Mercado Pago',
                'slug' => 'mercadopago',
                'driver' => 'mercadopago',
                'active' => false,
                'test_mode' => true,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.0000,
                'sort_order' => 2,
                'supports_recurring' => false,
                'supports_refund' => true,
                'allowed_currencies' => ['BRL'],
                'due_days' => 3,
            ],
            [
                'name' => 'Efí Pro (Gerencianet)',
                'slug' => 'efirpro',
                'driver' => 'efirpro',
                'active' => false,
                'test_mode' => true,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.0000,
                'sort_order' => 3,
                'supports_recurring' => false,
                'supports_refund' => true,
                'allowed_currencies' => ['BRL'],
                'due_days' => 3,
            ],
            [
                'name' => 'Banco Inter',
                'slug' => 'bancointer',
                'driver' => 'bancointer',
                'active' => false,
                'test_mode' => true,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.0000,
                'sort_order' => 4,
                'supports_recurring' => false,
                'supports_refund' => true,
                'allowed_currencies' => ['BRL'],
                'due_days' => 3,
            ],
            [
                'name' => 'Banco do Brasil',
                'slug' => 'bancobrasil',
                'driver' => 'bancobrasil',
                'active' => false,
                'test_mode' => true,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.0000,
                'sort_order' => 5,
                'supports_recurring' => false,
                'supports_refund' => true,
                'allowed_currencies' => ['BRL'],
                'due_days' => 3,
            ],
            [
                'name' => 'PagBank',
                'slug' => 'pagbank',
                'driver' => 'pagbank',
                'active' => false,
                'test_mode' => true,
                'fee_fixed' => 0.00,
                'fee_percentage' => 0.0000,
                'sort_order' => 6,
                'supports_recurring' => false,
                'supports_refund' => true,
                'allowed_currencies' => ['BRL'],
                'due_days' => 3,
            ],
        ];

        foreach ($defaultGateways as $gateway) {
            \DB::table('gateways')->updateOrInsert(
                ['driver' => $gateway['driver']],
                $gateway
            );
        }

        // Adiciona campos que podem estar faltando na tabela gateways
        if (Schema::hasTable('gateways')) {
            Schema::table('gateways', function (Blueprint $table) {
                if (!Schema::hasColumn('gateways', 'due_days')) {
                    $table->integer('due_days')->default(3)->after('sort_order');
                }
                if (!Schema::hasColumn('gateways', 'slug')) {
                    $table->string('slug')->nullable()->after('name');
                }
            });

            // Atualiza slugs se estiverem vazios
            \DB::table('gateways')->whereNull('slug')->orWhere('slug', '')->update([
                'slug' => \DB::raw('driver')
            ]);
        }
    }

    public function down(): void
    {
        // Remove apenas os gateways padrão criados por esta migração
        \DB::table('gateways')->whereIn('driver', [
            'paghiper', 'mercadopago', 'efirpro', 'bancointer', 'bancobrasil', 'pagbank'
        ])->delete();

        if (Schema::hasTable('gateways')) {
            Schema::table('gateways', function (Blueprint $table) {
                if (Schema::hasColumn('gateways', 'due_days')) {
                    $table->dropColumn('due_days');
                }
                if (Schema::hasColumn('gateways', 'slug')) {
                    $table->dropColumn('slug');
                }
            });
        }
    }
};