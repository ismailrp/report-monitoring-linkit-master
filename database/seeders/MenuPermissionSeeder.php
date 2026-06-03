<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MenuPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Creates permissions for all menu items and assigns them to super_admin.
     */
    public function run(): void
    {
        // All menu permissions
        $permissions = [
            // Dashboard
            'view_dashboard',
            
            // Master Data
            'view_any_user',
            'view_any_company',
            'view_any_country',
            'view_any_merchant',
            'view_any_operator',
            'view_any_service',
            'view_any_alert',
            'view_any_role',

            // Reports
            'view_any_summary_daily',
            'view_any_summary_weekly',
            'view_any_mo_hour',
            'view_any_sr_hour',
            'view_any_sub_active_user',
            'view_any_transaction_hour',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Give super_admin ALL permissions
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $superAdmin->syncPermissions(Permission::all());

        $this->command->info('Menu permissions created and assigned to super_admin.');
    }
}
