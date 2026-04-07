<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('server_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('fill_type', ['sequential', 'least_used', 'random'])->default('least_used');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('hostname');
            $table->string('ip_address', 45);
            $table->string('ip_address_secondary', 45)->nullable();
            $table->unsignedSmallInteger('port')->default(2087);
            $table->enum('type', ['shared', 'reseller', 'vps', 'dedicated', 'other'])->default('shared');
            $table->enum('module', ['cpanel', 'whm', 'plesk', 'directadmin', 'ispconfig', 'aapanel', 'none'])->default('cpanel');
            $table->string('username')->nullable();
            $table->text('api_key')->nullable()->comment('WHM API token - never expose as plain text');
            $table->string('api_hash')->nullable()->comment('Legacy WHM hash auth');
            $table->unsignedSmallInteger('max_accounts')->default(0)->comment('0 = unlimited');
            $table->unsignedInteger('current_accounts')->default(0);
            $table->boolean('secure')->default(true);
            $table->boolean('active')->default(true);
            $table->enum('status', ['online', 'offline', 'maintenance', 'unknown'])->default('unknown');
            $table->timestamp('last_check_at')->nullable();
            $table->string('os_name')->nullable();
            $table->string('os_version')->nullable();
            $table->string('kernel')->nullable();
            $table->string('cpanel_version')->nullable();
            $table->string('php_version_default')->nullable();
            $table->json('php_versions_available')->nullable();
            $table->string('nameserver1')->nullable();
            $table->string('nameserver2')->nullable();
            $table->string('nameserver3')->nullable();
            $table->json('notes')->nullable();
            $table->json('meta')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['status', 'active']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('servers');
        Schema::dropIfExists('server_groups');
    }
};
