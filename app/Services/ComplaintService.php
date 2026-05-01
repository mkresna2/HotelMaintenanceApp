<?php

namespace App\Services;

use App\Models\Complaint;
use App\Models\WorkOrder;
use App\Models\User;
use App\Models\Technician;
use App\Models\Asset;
use App\Notifications\ComplaintStatusChanged;
use App\Notifications\WorkOrderAssigned;
use App\Notifications\SlaBreached;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComplaintService
{
    /**
     * Create a new guest complaint and automatically generate a linked work order.
     * Implements GC-01, GC-02, GC-03
     */
    public function createComplaint(array $data): Complaint
    {
        return DB::transaction(function () use ($data) {
            // Auto-classify if not provided (GC-02)
            if (empty($data['category'])) {
                $data['category'] = $this->autoClassifyComplaint($data['description'] ?? '');
            }

            // Create the complaint
            $complaint = Complaint::create([
                'complaint_id' => $this->generateComplaintNumber(),
                'guest_name' => $data['guest_name'] ?? null,
                'room_number' => $data['room_number'],
                'category' => $data['category'],
                'description' => $data['description'],
                'priority' => $data['priority'] ?? 'medium',
                'status' => 'open',
                'reported_by' => $data['reported_by'] ?? auth()->id(),
                'channel' => $data['channel'] ?? 'front_desk', // front_desk, mobile, qr_code, pms
                'reported_at' => now(),
            ]);

            // Auto-generate linked work order (GC-03)
            $this->createLinkedWorkOrder($complaint, $data);

            // Log initial status
            $complaint->logs()->create([
                'status' => 'open',
                'note' => 'Complaint logged via ' . $data['channel'],
                'user_id' => auth()->id(),
            ]);

            return $complaint;
        });
    }

    /**
     * Update complaint status and trigger notifications.
     * Implements GC-04, GC-05
     */
    public function updateComplaintStatus(Complaint $complaint, string $newStatus, ?string $note = null): Complaint
    {
        $oldStatus = $complaint->status;
        $complaint->status = $newStatus;
        
        if ($newStatus === 'resolved') {
            $complaint->resolved_at = now();
        } elseif ($newStatus === 'closed') {
            $complaint->closed_at = now();
        }

        $complaint->save();

        // Log status change
        $complaint->logs()->create([
            'status' => $newStatus,
            'previous_status' => $oldStatus,
            'note' => $note,
            'user_id' => auth()->id(),
        ]);

        // Send notification to guest if channel supports it (GC-05)
        if (in_array($complaint->channel, ['mobile', 'qr_code']) && $complaint->guest_contact) {
            $complaint->notify(new ComplaintStatusChanged($complaint, $newStatus));
        }

        // Check for escalation if still open and high priority
        if (in_array($newStatus, ['open', 'in_progress'])) {
            $this->checkSlaEscalation($complaint);
        }

        return $complaint;
    }

    /**
     * Add follow-up note to complaint.
     * Implements GC-07
     */
    public function addFollowUp(Complaint $complaint, array $data): void
    {
        $complaint->followUps()->create([
            'note' => $data['note'],
            'follow_up_type' => $data['type'] ?? 'internal', // internal, guest_communication
            'user_id' => auth()->id(),
        ]);
    }

    /**
     * Check SLA and trigger escalation if needed.
     * Implements GC-06
     */
    public function checkSlaEscalation(Complaint $complaint): void
    {
        $slaHours = $this->getSlaHours($complaint->priority);
        $elapsedHours = now()->diffInHours($complaint->reported_at);

        if ($elapsedHours > $slaHours && !$complaint->escalated) {
            $complaint->escalated = true;
            $complaint->save();

            // Notify supervisor
            $supervisors = User::role('supervisor')->get();
            foreach ($supervisors as $supervisor) {
                $supervisor->notify(new SlaBreached($complaint, 'complaint'));
            }

            Log::warning("Complaint {$complaint->complaint_id} SLA breached - escalated");
        }
    }

    /**
     * Create a work order linked to a complaint.
     */
    protected function createLinkedWorkOrder(Complaint $complaint, array $data): WorkOrder
    {
        $workOrderService = app(WorkOrderService::class);
        
        return $workOrderService->createWorkOrder([
            'title' => "Maintenance: " . substr($complaint->description, 0, 50),
            'description' => $complaint->description,
            'type' => 'corrective',
            'priority' => $complaint->priority,
            'location_type' => 'room',
            'location_value' => $complaint->room_number,
            'complaint_id' => $complaint->id,
            'asset_id' => $data['asset_id'] ?? null,
            'assigned_to' => $data['assigned_to'] ?? null,
            'created_by' => $complaint->reported_by,
            'source' => 'complaint',
        ]);
    }

    /**
     * Auto-classify complaint based on description keywords.
     */
    protected function autoClassifyComplaint(string $description): string
    {
        $description = strtolower($description);
        
        $keywords = [
            'plumbing' => ['leak', 'water', 'toilet', 'sink', 'drain', 'pipe', 'faucet'],
            'electrical' => ['power', 'outlet', 'light', 'switch', 'electric', 'socket'],
            'hvac' => ['ac', 'air conditioning', 'heating', 'cold', 'hot', 'temperature', 'vent'],
            'furniture' => ['bed', 'chair', 'table', 'broken', 'damaged', 'drawer'],
            'appliance' => ['tv', 'remote', 'minibar', 'fridge', 'coffee', 'microwave'],
        ];

        foreach ($keywords as $category => $words) {
            foreach ($words as $word) {
                if (str_contains($description, $word)) {
                    return $category;
                }
            }
        }

        return 'general';
    }

    /**
     * Get SLA hours based on priority.
     */
    protected function getSlaHours(string $priority): int
    {
        return match($priority) {
            'critical' => 1,
            'high' => 2,
            'medium' => 4,
            'low' => 8,
            default => 4,
        };
    }

    /**
     * Generate unique complaint number.
     */
    protected function generateComplaintNumber(): string
    {
        $prefix = 'CMP-' . date('Ymd');
        $lastComplaint = Complaint::where('complaint_id', 'like', $prefix . '%')
            ->orderBy('complaint_id', 'desc')
            ->first();

        if ($lastComplaint) {
            $lastNumber = (int) substr($lastComplaint->complaint_id, -4);
            return $prefix . '-' . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
        }

        return $prefix . '-0001';
    }

    /**
     * Get complaints with analytics data.
     * Implements GC-09
     */
    public function getComplaintAnalytics(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $query = Complaint::query();
        
        if ($startDate) {
            $query->where('reported_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('reported_at', '<=', $endDate);
        }

        $total = $query->count();
        $resolved = (clone $query)->where('status', 'resolved')->count();
        $open = (clone $query)->whereIn('status', ['open', 'in_progress'])->count();
        
        $byCategory = (clone $query)->select('category', DB::raw('count(*) as count'))
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();
            
        $byPriority = (clone $query)->select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get();
            
        $avgResolutionTime = Complaint::whereNotNull('resolved_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, reported_at, resolved_at)) as avg_minutes')
            ->value('avg_minutes');

        return [
            'total' => $total,
            'resolved' => $resolved,
            'open' => $open,
            'resolution_rate' => $total > 0 ? round(($resolved / $total) * 100, 2) : 0,
            'avg_resolution_minutes' => round($avgResolutionTime ?? 0, 2),
            'by_category' => $byCategory,
            'by_priority' => $byPriority,
        ];
    }
}
