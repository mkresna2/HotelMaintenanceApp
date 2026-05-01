<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Complaint;
use App\Models\Asset;
use App\Models\MaintenanceSchedule;
use App\Models\Notification;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $role = $user->role->name;

        // Base stats for all users
        $stats = [
            'open_work_orders' => WorkOrder::where('status', 'open')->count(),
            'pending_complaints' => Complaint::whereIn('status', ['open', 'in_progress'])->count(),
            'overdue_tasks' => WorkOrder::where('status', '!=', 'closed')
                ->where('due_date', '<', now())
                ->count(),
            'pm_compliance' => $this->calculatePMCompliance(),
            'healthy_assets' => Asset::where('health_score', '>=', 80)->count(),
            'warning_assets' => Asset::whereBetween('health_score', [50, 79])->count(),
            'critical_assets' => Asset::where('health_score', '<', 50)->count(),
        ];

        $data = compact('stats');

        // Role-specific data
        if ($role === 'technician') {
            $data['myTasks'] = WorkOrder::where('assigned_to', $user->technician?->id)
                ->whereIn('status', ['open', 'in_progress'])
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } elseif ($role === 'front_desk') {
            $data['recentComplaints'] = Complaint::orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
        } elseif (in_array($role, ['admin', 'supervisor', 'manager'])) {
            $data['criticalAlerts'] = Notification::where('priority', 'high')
                ->where('read_at', null)
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            $data['teamStats'] = $this->getTeamPerformanceStats();
        }

        return view('dashboard.index', $data);
    }

    private function calculatePMCompliance()
    {
        $total = MaintenanceSchedule::count();
        if ($total === 0) return 0;
        
        $completed = MaintenanceSchedule::where('status', 'active')
            ->whereHas('workOrders', function($q) {
                $q->whereIn('status', ['resolved', 'closed']);
            })
            ->count();
        
        return round(($completed / $total) * 100);
    }

    private function getTeamPerformanceStats()
    {
        $today = now()->startOfDay();
        
        $completedToday = WorkOrder::whereDate('closed_at', $today)
            ->orWhereDate('resolved_at', $today)
            ->count();

        $avgResolutionTime = WorkOrder::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');
        
        $avgResolutionTimeFormatted = $avgResolutionTime 
            ? floor($avgResolutionTime / 60) . 'h ' . round($avgResolutionTime % 60) . 'm'
            : 'N/A';

        $slaCompliance = $this->calculateSLACompliance();

        return [
            'completed_today' => $completedToday,
            'avg_resolution_time' => $avgResolutionTimeFormatted,
            'resolution_percentage' => min(100, ($completedToday / max(1, WorkOrder::count())) * 1000),
            'sla_compliance' => $slaCompliance,
        ];
    }

    private function calculateSLACompliance()
    {
        $total = WorkOrder::whereNotNull('resolved_at')->count();
        if ($total === 0) return 100;

        $compliant = WorkOrder::whereNotNull('resolved_at')
            ->whereColumn('resolved_at', '<=', 'due_date')
            ->count();

        return round(($compliant / $total) * 100);
    }
}
