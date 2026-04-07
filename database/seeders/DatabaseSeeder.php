<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionSeeder::class,
            SettingSeeder::class,
            GatewaySeeder::class,
            EmailTemplateSeeder::class,
            KanbanSeeder::class,
            TicketDepartmentSeeder::class,
        ]);
    }
}
