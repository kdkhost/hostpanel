<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('icon')->nullable();
            $table->string('color', 7)->nullable();
            $table->boolean('show_on_order')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_group_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('server_group_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('welcome_email')->nullable();
            $table->enum('type', ['shared', 'reseller', 'vps', 'dedicated', 'domain', 'addon', 'other'])->default('shared');
            $table->string('module')->default('none')->comment('cpanel, plesk, directadmin, etc.');
            $table->enum('billing_cycle_type', ['one_time', 'recurring'])->default('recurring');
            $table->boolean('require_domain')->default(false);
            $table->boolean('auto_setup')->default(true);
            $table->enum('auto_setup_type', ['payment', 'order', 'manual'])->default('payment');
            $table->json('configurable_options')->nullable();
            $table->json('custom_fields')->nullable();
            $table->string('cpanel_pkg')->nullable()->comment('WHM package name');
            $table->unsignedSmallInteger('disk_space')->nullable()->comment('MB');
            $table->unsignedSmallInteger('bandwidth')->nullable()->comment('GB');
            $table->unsignedSmallInteger('subdomains')->nullable();
            $table->unsignedSmallInteger('email_accounts')->nullable();
            $table->unsignedSmallInteger('databases')->nullable();
            $table->unsignedSmallInteger('ftp_accounts')->nullable();
            $table->boolean('ssl_free')->default(false);
            $table->boolean('featured')->default(false);
            $table->boolean('hidden')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('stock_control_type')->default('unlimited')->comment('unlimited, quantity');
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->string('image')->nullable();
            $table->json('features')->nullable();
            $table->json('meta')->nullable();
            $table->boolean('active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['type', 'active']);
            $table->index(['product_group_id', 'active']);
        });

        Schema::create('product_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('BRL');
            $table->enum('billing_cycle', [
                'one_time', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'
            ])->default('monthly');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['product_id', 'currency', 'billing_cycle']);
            $table->index('product_id');
        });

        Schema::create('product_addons', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('products')->nullable()->comment('null = all products');
            $table->boolean('global')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::create('product_addon_pricing', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_addon_id')->constrained()->cascadeOnDelete();
            $table->string('currency', 3)->default('BRL');
            $table->enum('billing_cycle', [
                'one_time', 'monthly', 'quarterly', 'semiannually', 'annually', 'biennially', 'triennially'
            ])->default('monthly');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('setup_fee', 10, 2)->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index('product_addon_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_addon_pricing');
        Schema::dropIfExists('product_addons');
        Schema::dropIfExists('product_pricing');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_groups');
    }
};
