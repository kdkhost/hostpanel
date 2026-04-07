<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurrence_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('gateway');
            $table->string('gateway_subscription_id')->nullable();
            $table->string('gateway_plan_id')->nullable();
            $table->enum('status', ['active', 'suspended', 'cancelled', 'past_due'])->default('active');
            $table->string('billing_cycle');
            $table->decimal('amount', 10, 2);
            $table->string('currency', 3)->default('BRL');
            $table->date('next_billing_date')->nullable();
            $table->date('cancelled_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['gateway', 'gateway_subscription_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurrence_subscriptions');
    }
};
