<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            // Adiciona campos para auditoria de crédito se não existirem
            if (!Schema::hasColumn('transactions', 'source')) {
                $table->string('source')->nullable()->after('description'); // manual, refund, affiliate, bonus, etc
            }
            if (!Schema::hasColumn('transactions', 'created_by')) {
                $table->string('created_by')->nullable()->after('source'); // admin, system, client
            }
            
            // Adiciona constraint único para evitar transações duplicadas
            // Permite múltiplas transações NULL (para transações manuais sem gateway_transaction_id)
            $table->unique(['gateway', 'gateway_transaction_id'], 'unique_gateway_transaction')
                  ->where('gateway_transaction_id', '!=', null);
        });

        // Adiciona campos de auditoria na tabela credits se não existirem
        if (Schema::hasTable('credits')) {
            Schema::table('credits', function (Blueprint $table) {
                if (!Schema::hasColumn('credits', 'source')) {
                    $table->string('source')->default('manual')->after('description');
                }
                if (!Schema::hasColumn('credits', 'created_by')) {
                    $table->string('created_by')->default('admin')->after('source');
                }
            });
        }

        // Adiciona campos de configuração de cron se não existirem
        if (Schema::hasTable('settings')) {
            $cronSettings = [
                ['group' => 'cron', 'key' => 'generate_invoices.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'generate_invoices.schedule', 'value' => '0 8 * * *', 'type' => 'string'],
                ['group' => 'cron', 'key' => 'suspend_overdue.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'suspend_overdue.schedule', 'value' => '0 9 * * *', 'type' => 'string'],
                ['group' => 'cron', 'key' => 'server_health.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'server_health.schedule', 'value' => '*/5 * * * *', 'type' => 'string'],
                ['group' => 'cron', 'key' => 'late_fees.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'late_fees.schedule', 'value' => '0 0 * * *', 'type' => 'string'],
                ['group' => 'cron', 'key' => 'affiliate_commissions.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'affiliate_commissions.schedule', 'value' => '0 2 * * *', 'type' => 'string'],
                ['group' => 'cron', 'key' => 'cleanup_tokens.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'cleanup_tokens.schedule', 'value' => '0 3 * * *', 'type' => 'string'],
                ['group' => 'cron', 'key' => 'due_reminders.enabled', 'value' => 'true', 'type' => 'boolean'],
                ['group' => 'cron', 'key' => 'due_reminders.schedule', 'value' => '0 10 * * *', 'type' => 'string'],
                
                // Configurações de multa e juros
                ['group' => 'billing', 'key' => 'late_fees_enabled', 'value' => 'false', 'type' => 'boolean'],
                ['group' => 'billing', 'key' => 'late_fee_percent', 'value' => '2.0', 'type' => 'decimal'],
                ['group' => 'billing', 'key' => 'interest_daily', 'value' => '0.033', 'type' => 'decimal'],
                ['group' => 'billing', 'key' => 'max_late_fee_percent', 'value' => '50.0', 'type' => 'decimal'],
                ['group' => 'billing', 'key' => 'late_fee_grace_days', 'value' => '1', 'type' => 'integer'],
                ['group' => 'billing', 'key' => 'invoice_prefix', 'value' => 'INV', 'type' => 'string'],
                
                // Configurações de lembretes
                ['group' => 'billing', 'key' => 'reminder_days_1', 'value' => '3', 'type' => 'integer'],
                ['group' => 'billing', 'key' => 'reminder_days_2', 'value' => '1', 'type' => 'integer'],
                ['group' => 'billing', 'key' => 'reminder_days_3', 'value' => '0', 'type' => 'integer'],
                
                // Configurações de afiliados
                ['group' => 'affiliate', 'key' => 'min_commission', 'value' => '0.01', 'type' => 'decimal'],
                ['group' => 'affiliate', 'key' => 'auto_approve_commissions', 'value' => 'true', 'type' => 'boolean'],
            ];

            foreach ($cronSettings as $setting) {
                \DB::table('settings')->updateOrInsert(
                    ['group' => $setting['group'], 'key' => $setting['key']],
                    $setting
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropUnique('unique_gateway_transaction');
            $table->dropColumn(['source', 'created_by']);
        });

        if (Schema::hasTable('credits')) {
            Schema::table('credits', function (Blueprint $table) {
                $table->dropColumn(['source', 'created_by']);
            });
        }
    }
};