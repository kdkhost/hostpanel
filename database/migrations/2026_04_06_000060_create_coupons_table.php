<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('description')->nullable();
            $table->enum('type', ['percentage', 'fixed'])->default('percentage');
            $table->decimal('value', 10, 2)->default(0);
            $table->boolean('applies_to_setup')->default(false);
            $table->boolean('recurring')->default(false);
            $table->unsignedSmallInteger('recurring_cycles')->default(0)->comment('0 = always');
            $table->unsignedInteger('max_uses')->default(0)->comment('0 = unlimited');
            $table->unsignedInteger('uses_count')->default(0);
            $table->unsignedSmallInteger('max_uses_per_client')->default(1);
            $table->json('allowed_products')->nullable()->comment('null = all products');
            $table->json('allowed_billing_cycles')->nullable();
            $table->date('starts_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->decimal('minimum_amount', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('code');
            $table->index(['active', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
