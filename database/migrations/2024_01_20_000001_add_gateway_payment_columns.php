<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Adiciona fee_amount e refunded_amount na tabela transactions
        Schema::table('transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('transactions', 'fee_amount')) {
                $table->decimal('fee_amount', 10, 2)->default(0)->after('amount');
            }
            if (!Schema::hasColumn('transactions', 'refunded_amount')) {
                $table->decimal('refunded_amount', 10, 2)->default(0)->after('fee_amount');
            }
        });

        // Adiciona whatsapp_enabled nos clients
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'whatsapp_enabled')) {
                $table->boolean('whatsapp_enabled')->default(true)->after('phone');
            }
        });

        // Adiciona pass_fee, late_fee_enabled, interest_daily, late_fee_percent nos gateways
        Schema::table('gateways', function (Blueprint $table) {
            if (!Schema::hasColumn('gateways', 'pass_fee')) {
                $table->boolean('pass_fee')->default(false)->after('fee_percentage');
            }
            if (!Schema::hasColumn('gateways', 'late_fee_enabled')) {
                $table->boolean('late_fee_enabled')->default(true)->after('pass_fee');
            }
            if (!Schema::hasColumn('gateways', 'late_fee_percent')) {
                $table->decimal('late_fee_percent', 5, 2)->default(2.00)->after('late_fee_enabled');
            }
            if (!Schema::hasColumn('gateways', 'interest_daily')) {
                $table->decimal('interest_daily', 5, 4)->default(0.0330)->after('late_fee_percent');
            }
            if (!Schema::hasColumn('gateways', 'due_days')) {
                $table->unsignedTinyInteger('due_days')->default(3)->after('interest_daily');
            }
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumnIfExists('fee_amount');
            $table->dropColumnIfExists('refunded_amount');
        });
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumnIfExists('whatsapp_enabled');
        });
        Schema::table('gateways', function (Blueprint $table) {
            $table->dropColumnIfExists('pass_fee');
            $table->dropColumnIfExists('late_fee_enabled');
            $table->dropColumnIfExists('late_fee_percent');
            $table->dropColumnIfExists('interest_daily');
            $table->dropColumnIfExists('due_days');
        });
    }
};
