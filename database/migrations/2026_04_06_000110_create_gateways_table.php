<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver')->comment('pix, stripe, paypal, bank_transfer, manual');
            $table->json('settings')->nullable()->comment('Encrypted credentials');
            $table->boolean('active')->default(false);
            $table->boolean('test_mode')->default(true);
            $table->decimal('fee_fixed', 8, 2)->default(0);
            $table->decimal('fee_percentage', 5, 4)->default(0);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('allowed_currencies')->nullable();
            $table->boolean('supports_recurring')->default(false);
            $table->boolean('supports_refund')->default(false);
            $table->timestamps();

            $table->index('active');
        });

        Schema::create('gateway_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gateway_id')->constrained()->cascadeOnDelete();
            $table->foreignId('transaction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event_type', 50);
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->boolean('success')->default(false);
            $table->text('error_message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['gateway_id', 'event_type']);
            $table->index('transaction_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gateway_logs');
        Schema::dropIfExists('gateways');
    }
};
