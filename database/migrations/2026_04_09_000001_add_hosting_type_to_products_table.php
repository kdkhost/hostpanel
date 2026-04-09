<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `products` MODIFY COLUMN `type` ENUM('shared','reseller','vps','dedicated','domain','addon','hosting','other') NOT NULL DEFAULT 'hosting'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `products` MODIFY COLUMN `type` ENUM('shared','reseller','vps','dedicated','domain','addon','other') NOT NULL DEFAULT 'shared'");
    }
};
