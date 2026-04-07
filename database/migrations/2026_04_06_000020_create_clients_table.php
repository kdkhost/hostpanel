<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('document_type', 10)->default('cpf')->comment('cpf, cnpj, passport');
            $table->string('document_number', 20)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('whatsapp', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->string('company_name')->nullable();
            $table->string('company_position')->nullable();
            $table->string('address')->nullable();
            $table->string('address_number', 20)->nullable();
            $table->string('address_complement', 100)->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('country', 3)->default('BRL');
            $table->string('ibge_code', 10)->nullable();
            $table->string('avatar')->nullable();
            $table->string('language', 10)->default('pt_BR');
            $table->string('currency', 3)->default('BRL');
            $table->enum('status', ['active', 'inactive', 'suspended', 'blocked', 'pending'])->default('pending');
            $table->boolean('email_verified')->default(false);
            $table->timestamp('email_verified_at')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->text('two_factor_secret')->nullable();
            $table->text('two_factor_recovery_codes')->nullable();
            $table->timestamp('two_factor_confirmed_at')->nullable();
            $table->boolean('marketing_consent')->default(false);
            $table->boolean('terms_accepted')->default(false);
            $table->timestamp('terms_accepted_at')->nullable();
            $table->decimal('credit_balance', 10, 2)->default(0);
            $table->string('notes')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip', 45)->nullable();
            $table->boolean('is_protected')->default(false)->comment('Impersonation blocked');
            $table->json('meta')->nullable();
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['email', 'status']);
            $table->index('document_number');
            $table->index('postcode');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clients');
    }
};
