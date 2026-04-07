<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_tlds', function (Blueprint $table) {
            $table->id();
            $table->string('tld', 30)->unique();
            $table->string('registrar')->nullable()->comment('Driver name');
            $table->decimal('price_register', 10, 2)->default(0);
            $table->decimal('price_transfer', 10, 2)->default(0);
            $table->decimal('price_renew', 10, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->boolean('epp_required')->default(true);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['active', 'tld']);
        });

        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('tld', 30);
            $table->string('full_domain')->virtualAs("CONCAT(`name`, '.', `tld`)")->nullable();
            $table->string('registrar')->nullable();
            $table->string('registrar_id')->nullable();
            $table->enum('type', ['register', 'transfer', 'existing'])->default('register');
            $table->enum('status', [
                'pending', 'active', 'expired', 'transferred_away', 'cancelled', 'grace_period', 'redemption'
            ])->default('pending');
            $table->string('epp_code')->nullable();
            $table->string('nameserver1')->nullable();
            $table->string('nameserver2')->nullable();
            $table->string('nameserver3')->nullable();
            $table->string('nameserver4')->nullable();
            $table->boolean('auto_renew')->default(true);
            $table->boolean('locked')->default(true);
            $table->boolean('id_protection')->default(false);
            $table->date('registration_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('next_due_date')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('BRL');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
        Schema::dropIfExists('domain_tlds');
    }
};
