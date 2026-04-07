<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_id')->nullable()->constrained()->nullOnDelete();
            $table->string('product_name');
            $table->string('domain')->nullable();
            $table->string('username')->nullable()->comment('cPanel account username');
            $table->text('password_encrypted')->nullable()->comment('Encrypted hosting password');
            $table->string('server_hostname')->nullable();
            $table->string('server_ip', 45)->nullable();
            $table->string('nameserver1')->nullable();
            $table->string('nameserver2')->nullable();
            $table->enum('billing_cycle', [
                'one_time', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'
            ])->default('monthly');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->enum('status', [
                'pending', 'active', 'suspended', 'terminated', 'cancelled', 'fraud'
            ])->default('pending');
            $table->enum('provision_status', [
                'pending', 'processing', 'active', 'failed', 'na'
            ])->default('pending');
            $table->text('provision_log')->nullable();
            $table->timestamp('provisioned_at')->nullable();
            $table->date('registration_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->json('configurable_options')->nullable();
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index(['server_id', 'status']);
            $table->index('next_due_date');
            $table->index('domain');
        });

        Schema::create('service_addon_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_addon_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->decimal('price', 10, 2)->default(0);
            $table->enum('billing_cycle', [
                'one_time', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'
            ])->default('monthly');
            $table->enum('status', ['active', 'cancelled', 'suspended'])->default('active');
            $table->date('next_due_date')->nullable();
            $table->timestamps();

            $table->index('service_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_addon_subscriptions');
        Schema::dropIfExists('services');
    }
};
