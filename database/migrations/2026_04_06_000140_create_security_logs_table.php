<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('impersonation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('ended_at')->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('actions_log')->nullable();
            $table->timestamps();

            $table->index('admin_id');
            $table->index('client_id');
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('authenticatable');
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('device')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->boolean('success')->default(true);
            $table->string('fail_reason')->nullable();
            $table->string('country', 3)->nullable();
            $table->string('city')->nullable();
            $table->timestamps();

            $table->index(['authenticatable_type', 'authenticatable_id']);
            $table->index('ip_address');
            $table->index('created_at');
        });

        Schema::create('server_health_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('server_id')->constrained()->cascadeOnDelete();
            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('ram_usage', 5, 2)->nullable();
            $table->decimal('swap_usage', 5, 2)->nullable();
            $table->decimal('disk_usage', 5, 2)->nullable();
            $table->string('load_avg_1', 10)->nullable();
            $table->string('load_avg_5', 10)->nullable();
            $table->string('load_avg_15', 10)->nullable();
            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->unsignedInteger('account_count')->nullable();
            $table->enum('status', ['online', 'offline', 'warning', 'critical'])->default('online');
            $table->json('disk_partitions')->nullable();
            $table->json('services_status')->nullable();
            $table->json('raw_data')->nullable();
            $table->timestamp('checked_at')->useCurrent();
            $table->timestamps();

            $table->index(['server_id', 'checked_at']);
            $table->index('status');
        });

        Schema::create('ip_blocklist', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('reason')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();

            $table->index('ip_address');
        });

        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('name');
            $table->string('token', 80)->unique();
            $table->json('abilities')->nullable();
            $table->json('allowed_ips')->nullable();
            $table->enum('type', ['client', 'admin', 'service'])->default('client');
            $table->unsignedInteger('rate_limit')->default(60);
            $table->boolean('active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index('token');
            $table->index('client_id');
        });

        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('url');
            $table->string('secret')->nullable();
            $table->json('events');
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('max_retries')->default(3);
            $table->timestamps();
        });

        Schema::create('webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('webhook_id')->constrained()->cascadeOnDelete();
            $table->string('event');
            $table->json('payload');
            $table->unsignedSmallInteger('response_code')->nullable();
            $table->text('response_body')->nullable();
            $table->boolean('success')->default(false);
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->timestamps();

            $table->index('webhook_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webhook_logs');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('api_tokens');
        Schema::dropIfExists('ip_blocklist');
        Schema::dropIfExists('server_health_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('impersonation_logs');
    }
};
