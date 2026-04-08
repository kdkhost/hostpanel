<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `servers` MODIFY COLUMN `module` ENUM('cpanel','whm','plesk','directadmin','ispconfig','aapanel','none') NOT NULL DEFAULT 'cpanel'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE `servers` MODIFY COLUMN `module` ENUM('cpanel','plesk','directadmin','ispconfig','none') NOT NULL DEFAULT 'cpanel'");
    }
};
