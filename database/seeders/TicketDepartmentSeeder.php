<?php

namespace Database\Seeders;

use App\Models\TicketDepartment;
use Illuminate\Database\Seeder;

class TicketDepartmentSeeder extends Seeder
{
    public function run(): void
    {
        $departments = [
            ['name' => 'Suporte Técnico',  'email' => 'suporte@hostpanel.com',    'public' => true,  'sla_hours' => 4,  'sort_order' => 1],
            ['name' => 'Financeiro',       'email' => 'financeiro@hostpanel.com', 'public' => true,  'sla_hours' => 8,  'sort_order' => 2],
            ['name' => 'Comercial',        'email' => 'comercial@hostpanel.com',  'public' => true,  'sla_hours' => 24, 'sort_order' => 3],
            ['name' => 'Abuse / Segurança','email' => 'abuse@hostpanel.com',      'public' => false, 'sla_hours' => 2,  'sort_order' => 4],
        ];

        foreach ($departments as $dept) {
            TicketDepartment::firstOrCreate(
                ['name' => $dept['name']],
                array_merge($dept, ['active' => true])
            );
        }

        $this->command->info('Ticket departments seeded.');
    }
}
