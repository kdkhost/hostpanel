<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('coupon_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('status', [
                'pending', 'active', 'fraud', 'cancelled'
            ])->default('pending');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->string('payment_method')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('accepted_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('number');
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('domain')->nullable();
            $table->enum('billing_cycle', [
                'one_time', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'
            ])->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->decimal('discount', 10, 2)->default(0);
            $table->json('configurable_options')->nullable();
            $table->json('custom_fields')->nullable();
            $table->json('addons')->nullable();
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
