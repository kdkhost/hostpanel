<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('session_id', 64)->nullable()->index();
            $table->foreignId('product_id')->constrained();
            $table->string('billing_cycle', 20);
            $table->string('domain')->nullable();
            $table->json('custom_fields')->nullable(); // Configurações específicas do produto
            $table->decimal('price', 10, 2);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('coupon_code', 50)->nullable();
            $table->decimal('discount', 10, 2)->default(0);
            $table->timestamp('expires_at'); // Expiração 24h
            $table->timestamps();

            $table->index(['client_id', 'expires_at']);
            $table->index(['session_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
