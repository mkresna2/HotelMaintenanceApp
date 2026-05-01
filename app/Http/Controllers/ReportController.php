<?php

namespace App\Http\Controllers;

use App\Models\WorkOrder;
use App\Models\Complaint;
use App\Models\Asset;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        $data = [
            'workOrderStats' => $this->getWorkOrderStats(),
            'complaintStats' => $this->getComplaintStats(),
            'assetStats' => $this->getAssetStats(),
        ];
        
        return view('reports.index', $data);
    }

    public function export(Request $request)
    {
        $type = $request->get('type', 'work-orders');
        
        // In production, this would generate PDF/Excel
        return back()->with('success', 'Report export initiated. Check your email for the download link.');
    }

    private function getWorkOrderStats()
    {
        return [
            'total' => WorkOrder::count(),
            'by_status' => WorkOrder::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'by_priority' => WorkOrder::selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority'),
            'avg_resolution_time' => $this->calculateAvgResolutionTime(),
            'mttr' => $this->calculateMTTR(),
        ];
    }

    private function getComplaintStats()
    {
        return [
            'total' => Complaint::count(),
            'by_category' => Complaint::selectRaw('category, COUNT(*) as count')
                ->groupBy('category')
                ->get()
                ->pluck('count', 'category'),
            'by_room' => Complaint::selectRaw('room_number, COUNT(*) as count')
                ->groupBy('room_number')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            'resolution_rate' => $this->calculateComplaintResolutionRate(),
        ];
    }

    private function getAssetStats()
    {
        return [
            'total' => Asset::count(),
            'by_health' => [
                'healthy' => Asset::where('health_score', '>=', 80)->count(),
                'warning' => Asset::whereBetween('health_score', [50, 79])->count(),
                'critical' => Asset::where('health_score', '<', 50)->count(),
            ],
            'by_category' => Asset::selectRaw('asset_category_id, COUNT(*) as count')
                ->groupBy('asset_category_id')
                ->with('category')
                ->get(),
            'warranty_expiring_soon' => Asset::whereBetween('warranty_expiry', [now(), now()->addDays(30)])
                ->count(),
        ];
    }

    private function calculateAvgResolutionTime()
    {
        $avg = WorkOrder::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_hours')
            ->value('avg_hours');
        
        return $avg ? round($avg, 1) . ' hours' : 'N/A';
    }

    private function calculateMTTR()
    {
        // Mean Time To Repair - only for completed work orders
        $mttr = WorkOrder::whereNotNull('closed_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, closed_at)) as mttr_minutes')
            ->value('mttr_minutes');
        
        if (!$mttr) return 'N/A';
        
        $hours = floor($mttr / 60);
        $minutes = round($mttr % 60);
        
        return "{$hours}h {$minutes}m";
    }

    private function calculateComplaintResolutionRate()
    {
        $total = Complaint::count();
        if ($total === 0) return 0;
        
        $resolved = Complaint::whereIn('status', ['resolved', 'closed'])->count();
        
        return round(($resolved / $total) * 100, 1) . '%';
    }
}
