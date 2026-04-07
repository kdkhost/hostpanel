<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Clientes
            'view_clients', 'create_clients', 'edit_clients', 'delete_clients',
            'impersonate_clients', 'add_client_credit',

            // Serviços
            'view_services', 'manage_services', 'suspend_services',
            'reactivate_services', 'terminate_services', 'provision_services',

            // Faturas
            'view_invoices', 'create_invoices', 'manage_invoices',
            'apply_payments', 'cancel_invoices',

            // Tickets
            'view_tickets', 'reply_tickets', 'assign_tickets',
            'close_tickets', 'manage_ticket_departments',

            // Servidores
            'view_servers', 'manage_servers', 'health_check_servers',

            // Produtos
            'view_products', 'manage_products',

            // Pedidos
            'view_orders', 'manage_orders', 'activate_orders',

            // Domínios
            'view_domains', 'manage_domains',

            // Cupons
            'view_coupons', 'manage_coupons',

            // Gateways
            'view_gateways', 'manage_gateways',

            // Relatórios
            'view_reports',

            // CMS
            'manage_cms',

            // Configurações
            'manage_settings',

            // Administradores
            'view_admins', 'manage_admins', 'manage_permissions',

            // Logs
            'view_logs',

            // Kanban
            'use_kanban',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'admin']);
        }

        // Super Admin - todas as permissões
        $superAdmin = Role::firstOrCreate(['name' => 'super-admin', 'guard_name' => 'admin']);
        $superAdmin->syncPermissions(Permission::where('guard_name', 'admin')->get());

        // Admin - permissões gerais (sem manage_admins/manage_permissions)
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'admin']);
        $adminRole->syncPermissions(Permission::where('guard_name', 'admin')
            ->whereNotIn('name', ['manage_admins', 'manage_permissions', 'manage_settings'])
            ->get());

        // Suporte - apenas tickets e visualização
        $support = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'admin']);
        $support->syncPermissions([
            'view_clients', 'view_services', 'view_invoices', 'view_orders',
            'view_tickets', 'reply_tickets', 'assign_tickets', 'close_tickets',
            'view_reports', 'use_kanban',
        ]);

        // Financeiro - foco em faturas
        $financial = Role::firstOrCreate(['name' => 'financial', 'guard_name' => 'admin']);
        $financial->syncPermissions([
            'view_clients', 'view_services', 'view_invoices', 'create_invoices',
            'manage_invoices', 'apply_payments', 'cancel_invoices',
            'view_orders', 'manage_orders', 'view_reports', 'add_client_credit',
        ]);

        $this->command->info('Permissions and roles seeded.');
    }
}
