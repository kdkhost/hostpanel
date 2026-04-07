<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_departments', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('description')->nullable();
            $table->boolean('public')->default(true);
            $table->unsignedSmallInteger('sla_hours')->default(24);
            $table->boolean('auto_assign')->default(false);
            $table->boolean('active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('number')->unique();
            $table->foreignId('client_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ticket_department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('subject');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->enum('status', ['open', 'answered', 'customer_reply', 'on_hold', 'in_progress', 'closed'])->default('open');
            $table->unsignedSmallInteger('rating')->nullable()->comment('1-5 stars');
            $table->text('rating_comment')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_reply_at')->nullable();
            $table->timestamp('sla_due_at')->nullable();
            $table->boolean('sla_breached')->default(false);
            $table->json('tags')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['client_id', 'status']);
            $table->index('number');
            $table->index(['ticket_department_id', 'status']);
            $table->index('assigned_to');
        });

        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('message');
            $table->boolean('is_note')->default(false)->comment('Internal note');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('ticket_id');
        });

        Schema::create('ticket_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_reply_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size')->default(0);
            $table->timestamps();

            $table->index('ticket_reply_id');
        });

        Schema::create('quick_replies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('message');
            $table->foreignId('ticket_department_id')->nullable()->constrained()->nullOnDelete();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quick_replies');
        Schema::dropIfExists('ticket_attachments');
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('ticket_departments');
    }
};
