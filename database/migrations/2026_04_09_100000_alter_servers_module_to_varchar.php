<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `servers` MODIFY COLUMN `module` VARCHAR(50) NOT NULL DEFAULT 'cpanel'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `servers` MODIFY COLUMN `module` ENUM('cpanel','whm','plesk','directadmin','ispconfig','aapanel','none') NOT NULL DEFAULT 'cpanel'");
    }
};
