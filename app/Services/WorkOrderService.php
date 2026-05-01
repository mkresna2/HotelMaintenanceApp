<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\WorkOrderLog;
use App\Models\User;
use App\Models\Technician;
use App\Models\SparePart;
use App\Notifications\WorkOrderAssigned as WorkOrderAssignedNotification;
use App\Notifications\WorkOrderStatusChanged;
use App\Notifications\SlaBreached;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class WorkOrderService
{
    /**
     * Create a new work order with automatic numbering and SLA calculation.
     * Implements WO-01, WO-02, WO-03, WO-04, WO-05
     */
    public function createWorkOrder(array $data): WorkOrder
    {
        return DB::transaction(function () use ($data) {
            $workOrder = WorkOrder::create([
                'work_order_id' => $this->generateWorkOrderNumber(),
                'title' => $data['title'],
                'description' => $data['description'],
                'type' => $data['type'] ?? 'corrective', // corrective, preventive, emergency, inspection
                'priority' => $data['priority'] ?? 'medium', // critical, high, medium, low
                'status' => 'open',
                'location_type' => $data['location_type'] ?? 'general', // room, asset, area
                'location_value' => $data['location_value'] ?? null,
                'asset_id' => $data['asset_id'] ?? null,
                'assigned_to' => $data['assigned_to'] ?? null,
                'created_by' => $data['created_by'] ?? auth()->id(),
                'source' => $data['source'] ?? 'manual', // manual, complaint, schedule, inspection
                'complaint_id' => $data['complaint_id'] ?? null,
                'schedule_id' => $data['schedule_id'] ?? null,
                'sla_due_at' => $this->calculateSlaDueDate($data['priority'] ?? 'medium'),
                'estimated_hours' => $data['estimated_hours'] ?? null,
            ]);

            // Initial log entry
            $workOrder->logs()->create([
                'status' => 'open',
                'note' => 'Work order created via ' . $data['source'],
                'user_id' => $data['created_by'] ?? auth()->id(),
            ]);

            // Notify assigned technician if any
            if ($data['assigned_to']) {
                $technician = Technician::find($data['assigned_to']);
                if ($technician && $technician->user) {
                    $technician->user->notify(new WorkOrderAssignedNotification($workOrder));
                }
            }

            return $workOrder;
        });
    }

    /**
     * Assign work order to technician.
     * Implements WO-03
     */
    public function assignWorkOrder(WorkOrder $workOrder, ?int $technicianId, ?string $note = null): WorkOrder
    {
        $oldAssignee = $workOrder->assigned_to;
        
        $workOrder->assigned_to = $technicianId;
        
        if ($workOrder->status === 'open' && $technicianId) {
            $workOrder->status = 'in_progress';
        }
        
        $workOrder->save();

        // Log assignment
        $workOrder->logs()->create([
            'status' => $workOrder->status,
            'previous_status' => $oldAssignee ? 'assigned' : 'open',
            'note' => $note ?? ($technicianId ? "Assigned to technician {$technicianId}" : "Unassigned"),
            'user_id' => auth()->id(),
        ]);

        // Notify new technician
        if ($technicianId) {
            $technician = Technician::with('user')->find($technicianId);
            if ($technician && $technician->user) {
                $technician->user->notify(new WorkOrderAssignedNotification($workOrder));
            }
        }

        return $workOrder;
    }

    /**
     * Update work order status through lifecycle.
     * Implements WO-05
     */
    public function updateStatus(WorkOrder $workOrder, string $newStatus, ?string $note = null): WorkOrder
    {
        $validTransitions = [
            'open' => ['in_progress', 'pending_parts', 'cancelled'],
            'in_progress' => ['pending_parts', 'resolved', 'open'],
            'pending_parts' => ['in_progress', 'cancelled'],
            'resolved' => ['closed', 'in_progress'],
        ];

        $currentStatus = $workOrder->status;
        
        if (!isset($validTransitions[$currentStatus]) || 
            !in_array($newStatus, $validTransitions[$currentStatus])) {
            throw new \InvalidArgumentException("Invalid status transition from {$currentStatus} to {$newStatus}");
        }

        $oldStatus = $workOrder->status;
        $workOrder->status = $newStatus;

        if ($newStatus === 'resolved') {
            $workOrder->resolved_at = now();
            $workOrder->actual_hours = $workOrder->logs()
                ->whereNotNull('time_spent')
                ->sum('time_spent');
        } elseif ($newStatus === 'closed') {
            $workOrder->closed_at = now();
        } elseif ($newStatus === 'in_progress' && !$workOrder->started_at) {
            $workOrder->started_at = now();
        }

        $workOrder->save();

        // Log status change
        $workOrder->logs()->create([
            'status' => $newStatus,
            'previous_status' => $oldStatus,
            'note' => $note,
            'user_id' => auth()->id(),
        ]);

        // Check SLA on resolution
        if ($newStatus === 'resolved') {
            $this->checkSlaCompliance($workOrder);
        }

        return $workOrder;
    }

    /**
     * Log work done (time spent, parts used, notes).
     * Implements WO-07
     */
    public function logWork(WorkOrder $workOrder, array $data): WorkOrderLog
    {
        return DB::transaction(function () use ($workOrder, $data) {
            $log = $workOrder->logs()->create([
                'status' => $workOrder->status,
                'note' => $data['note'] ?? '',
                'time_spent' => $data['time_spent'] ?? 0, // in hours
                'user_id' => auth()->id(),
            ]);

            // Deduct spare parts if used
            if (!empty($data['parts_used'])) {
                foreach ($data['parts_used'] as $part) {
                    SparePart::decrementStock(
                        $part['spare_part_id'],
                        $part['quantity'],
                        $workOrder->id,
                        auth()->id()
                    );
                    
                    $workOrder->partsUsed()->attach($part['spare_part_id'], [
                        'quantity' => $part['quantity'],
                        'unit_cost' => $part['unit_cost'] ?? 0,
                        'used_by' => auth()->id(),
                    ]);
                }
            }

            // Update total cost
            $this->recalculateCost($workOrder);

            return $log;
        });
    }

    /**
     * Add attachment to work order.
     * Implements WO-06
     */
    public function addAttachment(WorkOrder $workOrder, string $filePath, string $fileType, ?string $description = null): void
    {
        $workOrder->attachments()->create([
            'file_path' => $filePath,
            'file_type' => $fileType, // photo, video, document
            'description' => $description,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Check SLA compliance and calculate metrics.
     * Implements WO-08
     */
    public function checkSlaCompliance(WorkOrder $workOrder): void
    {
        if (!$workOrder->resolved_at || !$workOrder->sla_due_at) {
            return;
        }

        $isBreached = $workOrder->resolved_at->gt($workOrder->sla_due_at);
        $workOrder->sla_breached = $isBreached;
        $workOrder->save();

        if ($isBreached) {
            Log::warning("Work Order {$workOrder->work_order_id} SLA breached");
            
            // Notify supervisor
            $supervisors = User::role('supervisor')->get();
            foreach ($supervisors as $supervisor) {
                $supervisor->notify(new SlaBreached($workOrder, 'work_order'));
            }
        }
    }

    /**
     * Get work order history for asset/room.
     * Implements WO-09
     */
    public function getHistoryByLocation(string $locationType, string $locationValue, int $limit = 50): array
    {
        $query = WorkOrder::where('location_type', $locationType)
            ->where('location_value', $locationValue)
            ->orderBy('created_at', 'desc')
            ->limit($limit);

        $workOrders = $query->with(['assignee', 'asset', 'complaint', 'logs'])->get();

        $recurringIssues = WorkOrder::where('location_type', $locationType)
            ->where('location_value', $locationValue)
            ->select('description', DB::raw('COUNT(*) as occurrence_count'))
            ->groupBy('description')
            ->having('occurrence_count', '>', 1)
            ->orderBy('occurrence_count', 'desc')
            ->get();

        return [
            'work_orders' => $workOrders,
            'total_count' => $query->count(),
            'recurring_issues' => $recurringIssues,
        ];
    }

    /**
     * Detect duplicate/recurring issues.
     * Implements WO-10
     */
    public function detectDuplicates(string $description, string $locationValue, ?int $assetId = null): array
    {
        $keywords = explode(' ', strtolower($description));
        $significantKeywords = array_filter($keywords, fn($word) => strlen($word) > 3);
        
        $query = WorkOrder::where('location_value', $locationValue)
            ->where('status', '!=', 'cancelled')
            ->orderBy('created_at', 'desc');

        if ($assetId) {
            $query->where('asset_id', $assetId);
        }

        // Simple keyword matching for duplicates
        foreach ($significantKeywords as $keyword) {
            $query->orWhere('description', 'LIKE', "%{$keyword}%");
        }

        $potentialDuplicates = $query->limit(5)->get();

        return [
            'has_potential_duplicates' => $potentialDuplicates->count() > 0,
            'count' => $potentialDuplicates->count(),
            'work_orders' => $potentialDuplicates,
        ];
    }

    /**
     * Calculate SLA due date based on priority.
     */
    protected function calculateSlaDueDate(string $priority): Carbon
    {
        $hours = match($priority) {
            'critical' => 1,
            'high' => 2,
            'medium' => 4,
            'low' => 8,
            default => 4,
        };

        return now()->addHours($hours);
    }

    /**
     * Generate unique work order number.
     */
    protected function generateWorkOrderNumber(): string
    {
        $prefix = 'WO-' . date('Ymd');
        $lastWorkOrder = WorkOrder::where('work_order_id', 'like', $prefix . '%')
            ->orderBy('work_order_id', 'desc')
            ->first();

        if ($lastWorkOrder) {
            $lastNumber = (int) substr($lastWorkOrder->work_order_id, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    /**
     * Recalculate total cost of work order.
     */
    protected function recalculateCost(WorkOrder $workOrder): void
    {
        $laborCost = $workOrder->logs()->sum('time_spent') * config('maintenance.labor_rate_per_hour', 50);
        $partsCost = $workOrder->partsUsed()->selectRaw('SUM(quantity * unit_cost) as total')->value('total') ?? 0;
        
        $workOrder->total_cost = $laborCost + $partsCost;
        $workOrder->save();
    }

    /**
     * Get work order analytics.
     * Implements RPT-03
     */
    public function getAnalytics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = WorkOrder::query();
        
        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        $total = $query->count();
        $completed = (clone $query)->where('status', 'closed')->count();
        $inProgress = (clone $query)->where('status', 'in_progress')->count();
        $overdue = (clone $query)->where('sla_due_at', '<', now())
            ->whereIn('status', ['open', 'in_progress', 'pending_parts'])->count();

        $byType = (clone $query)->select('type', DB::raw('count(*) as count'))
            ->groupBy('type')
            ->get();
            
        $byPriority = (clone $query)->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();

        $avgResolutionTime = WorkOrder::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');

        $mttr = WorkOrder::whereNotNull('resolved_at')
            ->where('type', 'corrective')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, started_at, resolved_at)) as mttr_minutes')
            ->value('mttr_minutes');

        $slaCompliance = WorkOrder::whereNotNull('resolved_at')
            ->selectRaw('SUM(CASE WHEN sla_breached = 0 THEN 1 ELSE 0 END) as compliant, COUNT(*) as total')
            ->first();
        
        $complianceRate = $slaCompliance->total > 0 
            ? round(($slaCompliance->compliant / $slaCompliance->total) * 100, 2) 
            : 0;

        return [
            'total' => $total,
            'completed' => $completed,
            'in_progress' => $inProgress,
            'overdue' => $overdue,
            'completion_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'avg_resolution_minutes' => round($avgResolutionTime ?? 0, 2),
            'mttr_minutes' => round($mttr ?? 0, 2),
            'sla_compliance_rate' => $complianceRate,
            'by_type' => $byType,
            'by_priority' => $byPriority,
        ];
    }

    /**
     * Get technician workload for balancing.
     */
    public function getTechnicianWorkload(?Carbon $date = null): array
    {
        $date = $date ?? now();
        
        $technicians = Technician::with(['user', 'activeWorkOrders' => function ($query) {
            $query->whereIn('status', ['open', 'in_progress', 'pending_parts']);
        }])->get();

        $workload = [];
        foreach ($technicians as $tech) {
            $activeCount = $tech->activeWorkOrders->count();
            $estimatedHours = $tech->activeWorkOrders->sum('estimated_hours');
            
            $workload[] = [
                'technician_id' => $tech->id,
                'name' => $tech->user?->name ?? 'N/A',
                'active_work_orders' => $activeCount,
                'estimated_hours' => $estimatedHours,
                'availability_score' => $this->calculateAvailabilityScore($activeCount, $estimatedHours),
            ];
        }

        // Sort by availability (lowest workload first)
        usort($workload, fn($a, $b) => $a['availability_score'] <=> $b['availability_score']);

        return $workload;
    }

    /**
     * Calculate availability score (lower is more available).
     */
    protected function calculateAvailabilityScore(int $activeCount, float $estimatedHours): float
    {
        return ($activeCount * 10) + ($estimatedHours * 1.5);
    }
}
