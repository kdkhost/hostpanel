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

        DB::statement(
            "ALTER TABLE `servers` MODIFY COLUMN `module` ENUM("
            . "'cpanel','whm','whmsonic','aapanel','btpanel','plesk','directadmin','ispconfig',"
            . "'blesta','cyberpanel','webuzo','hestia','virtualmin','none'"
            . ") NOT NULL DEFAULT 'cpanel'"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::table('servers')
            ->whereIn('module', ['whmsonic', 'btpanel', 'blesta', 'cyberpanel', 'webuzo', 'hestia', 'virtualmin'])
            ->update(['module' => 'none']);

        DB::statement(
            "ALTER TABLE `servers` MODIFY COLUMN `module` ENUM("
            . "'cpanel','whm','plesk','directadmin','ispconfig','aapanel','none'"
            . ") NOT NULL DEFAULT 'cpanel'"
        );
    }
};
