<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Afiliados — cada cliente pode ser um afiliado
        Schema::create('affiliates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->unique()->constrained('clients')->cascadeOnDelete();
            $table->string('referral_code', 32)->unique();
            $table->decimal('commission_rate', 5, 2)->default(10.00); // percentual
            $table->enum('commission_type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('balance', 12, 2)->default(0);
            $table->decimal('total_earned', 12, 2)->default(0);
            $table->decimal('total_withdrawn', 12, 2)->default(0);
            $table->unsignedInteger('total_referrals')->default(0);
            $table->unsignedInteger('total_conversions')->default(0);
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
            $table->text('payment_info')->nullable(); // PIX, conta bancária, etc.
            $table->timestamps();

            $table->index('referral_code');
            $table->index('status');
        });

        // Clientes referidos — rastreia quem indicou quem
        Schema::create('affiliate_referrals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('referred_client_id')->constrained('clients')->cascadeOnDelete();
            $table->string('ip', 45)->nullable();
            $table->string('landing_page', 500)->nullable();
            $table->boolean('converted')->default(false);
            $table->timestamp('converted_at')->nullable();
            $table->timestamps();

            $table->unique(['affiliate_id', 'referred_client_id']);
        });

        // Comissões geradas
        Schema::create('affiliate_commissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->foreignId('referral_id')->constrained('affiliate_referrals')->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->decimal('invoice_amount', 12, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('rate_applied', 5, 2);
            $table->enum('type', ['percentage', 'fixed']);
            $table->enum('status', ['pending', 'approved', 'paid', 'rejected'])->default('pending');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });

        // Saques / Pagamentos
        Schema::create('affiliate_payouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('affiliate_id')->constrained('affiliates')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->enum('method', ['pix', 'bank_transfer', 'credit'])->default('pix');
            $table->text('payment_details')->nullable();
            $table->enum('status', ['pending', 'processing', 'completed', 'rejected'])->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['affiliate_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('affiliate_payouts');
        Schema::dropIfExists('affiliate_commissions');
        Schema::dropIfExists('affiliate_referrals');
        Schema::dropIfExists('affiliates');
    }
};
