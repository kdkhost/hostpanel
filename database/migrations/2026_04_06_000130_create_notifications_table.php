<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('subject');
            $table->longText('body_html');
            $table->text('body_text')->nullable();
            $table->boolean('active')->default(true);
            $table->string('category')->default('general');
            $table->json('variables')->nullable()->comment('Available template variables');
            $table->timestamps();

            $table->index('slug');
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('message');
            $table->string('trigger')->nullable();
            $table->boolean('active')->default(true);
            $table->json('variables')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('notifiable');
            $table->string('channel', 30)->comment('email, whatsapp, in_app');
            $table->string('subject')->nullable();
            $table->text('message')->nullable();
            $table->string('recipient')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'bounced'])->default('pending');
            $table->text('error')->nullable();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->string('template_slug')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            // morphs() já cria index em notifiable_type + notifiable_id
            $table->index(['channel', 'status']);
        });

        Schema::create('in_app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('title');
            $table->text('message');
            $table->string('icon')->default('bell');
            $table->string('color')->default('blue');
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->boolean('read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['client_id', 'read']);
            $table->index(['admin_id', 'read']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('in_app_notifications');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('email_templates');
    }
};
