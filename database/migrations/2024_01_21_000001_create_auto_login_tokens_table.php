<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auto_login_tokens', function (Blueprint $table) {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('service_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->enum('type', ['invoice', 'ondemand', 'admin'])->default('admin');
            $table->enum('generated_by', ['admin', 'client', 'system'])->default('system');
            $table->string('panel_url', 1024)->nullable();
            $table->string('remote_ip', 45)->nullable();
            $table->string('used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index('token');
            $table->index(['service_id', 'type']);
            $table->index('expires_at');
        });

        // Adicionar campo module nos servidores caso não exista
        Schema::table('servers', function (Blueprint $table) {
            if (!Schema::hasColumn('servers', 'module')) {
                $table->string('module')->default('whm')->after('type');
            }
        });

        // Adicionar panel_url nos services para armazenar URL customizada
        Schema::table('services', function (Blueprint $table) {
            if (!Schema::hasColumn('services', 'panel_url')) {
                $table->string('panel_url')->nullable()->after('server_ip');
            }
            if (!Schema::hasColumn('services', 'panel_username')) {
                $table->string('panel_username')->nullable()->after('panel_url');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auto_login_tokens');
        Schema::table('servers', function (Blueprint $table) {
            $table->dropColumnIfExists('module');
        });
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumnIfExists('panel_url');
            $table->dropColumnIfExists('panel_username');
        });
    }
};
