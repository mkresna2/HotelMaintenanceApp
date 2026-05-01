<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WorkOrderStatus;

class WorkOrderStatusSeeder extends Seeder
{
    public function run(): void
    {
        $statuses = [
            ['name' => 'open', 'display_name' => 'Open', 'color' => '#dc3545', 'sort_order' => 1, 'is_final' => false],
            ['name' => 'in_progress', 'display_name' => 'In Progress', 'color' => '#0d6efd', 'sort_order' => 2, 'is_final' => false],
            ['name' => 'pending_parts', 'display_name' => 'Pending Parts', 'color' => '#ffc107', 'sort_order' => 3, 'is_final' => false],
            ['name' => 'resolved', 'display_name' => 'Resolved', 'color' => '#198754', 'sort_order' => 4, 'is_final' => false],
            ['name' => 'closed', 'display_name' => 'Closed', 'color' => '#6c757d', 'sort_order' => 5, 'is_final' => true],
            ['name' => 'cancelled', 'display_name' => 'Cancelled', 'color' => '#343a40', 'sort_order' => 6, 'is_final' => true],
        ];

        foreach ($statuses as $status) {
            WorkOrderStatus::firstOrCreate(['name' => $status['name']], $status);
        }
    }
}
