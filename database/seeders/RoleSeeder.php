<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'display_name' => 'Administrator',
                'description' => 'Full system access with all permissions',
                'permissions' => ['*'],
            ],
            [
                'name' => 'supervisor',
                'display_name' => 'Maintenance Supervisor',
                'description' => 'Chief Engineer - manages maintenance team and work orders',
                'permissions' => ['work_orders.manage', 'assets.view', 'complaints.view', 'reports.view', 'team.manage'],
            ],
            [
                'name' => 'technician',
                'display_name' => 'Maintenance Technician',
                'description' => 'Performs maintenance tasks and work orders',
                'permissions' => ['work_orders.view_assigned', 'work_orders.update', 'assets.view'],
            ],
            [
                'name' => 'front_desk',
                'display_name' => 'Front Desk Staff',
                'description' => 'Logs guest complaints and tracks resolution',
                'permissions' => ['complaints.create', 'complaints.view', 'work_orders.view'],
            ],
            [
                'name' => 'gm',
                'display_name' => 'General Manager',
                'description' => 'Hotel General Manager - views reports and dashboards',
                'permissions' => ['reports.view', 'dashboard.view', 'complaints.view', 'assets.view'],
            ],
            [
                'name' => 'finance',
                'display_name' => 'Finance/Procurement',
                'description' => 'Manages costs, parts inventory, and vendor payments',
                'permissions' => ['reports.view', 'parts.manage', 'vendors.manage', 'costs.view'],
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role['name']], $role);
        }
    }
}
