<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class WorkOrder extends Model
{
    use HasFactory;

    // Status constants
    const STATUS_OPEN = 'open';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_PENDING_PARTS = 'pending_parts';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';

    // Priority constants
    const PRIORITY_CRITICAL = 'critical';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    // Type constants
    const TYPE_CORRECTIVE = 'corrective';
    const TYPE_PREVENTIVE = 'preventive';
    const TYPE_EMERGENCY = 'emergency';
    const TYPE_INSPECTION = 'inspection';

    // SLA thresholds in hours
    const SLA_THRESHOLDS = [
        self::PRIORITY_CRITICAL => 2,
        self::PRIORITY_HIGH => 4,
        self::PRIORITY_MEDIUM => 8,
        self::PRIORITY_LOW => 24,
    ];

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'work_order_number',
        'title',
        'description',
        'type',
        'priority',
        'status',
        'asset_id',
        'department_id',
        'assigned_to',
        'created_by',
        'complaint_id',
        'maintenance_schedule_id',
        'preventive_task_id',
        'location_type',
        'location_id',
        'floor',
        'room_number',
        'due_date',
        'started_at',
        'resolved_at',
        'closed_at',
        'sla_deadline',
        'labor_hours',
        'labor_cost',
        'parts_cost',
        'total_cost',
        'resolution_notes',
        'guest_notified',
        'requires_followup',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'due_date' => 'datetime',
        'started_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'labor_hours' => 'decimal:2',
        'labor_cost' => 'decimal:2',
        'parts_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'guest_notified' => 'boolean',
        'requires_followup' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($workOrder) {
            if (!$workOrder->work_order_number) {
                $workOrder->work_order_number = 'WO-' . strtoupper(uniqid());
            }
            if (!$workOrder->sla_deadline && $workOrder->priority) {
                $workOrder->sla_deadline = now()->addHours(self::SLA_THRESHOLDS[$workOrder->priority] ?? 24);
            }
        });

        static::updating(function ($workOrder) {
            if ($workOrder->isDirty('labor_cost') || $workOrder->isDirty('parts_cost')) {
                $workOrder->total_cost = ($workOrder->labor_cost ?? 0) + ($workOrder->parts_cost ?? 0);
            }
            
            if ($workOrder->status === self::STATUS_RESOLVED && !$workOrder->resolved_at) {
                $workOrder->resolved_at = now();
            }
            
            if ($workOrder->status === self::STATUS_CLOSED && !$workOrder->closed_at) {
                $workOrder->closed_at = now();
            }
        });
    }

    /**
     * Get the asset for this work order.
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    /**
     * Get the department for this work order.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get the assigned technician.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'assigned_to');
    }

    /**
     * Get the user who created this work order.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the related complaint.
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * Get the maintenance schedule that generated this work order.
     */
    public function maintenanceSchedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class);
    }

    /**
     * Get the preventive task for this work order.
     */
    public function preventiveTask(): BelongsTo
    {
        return $this->belongsTo(PreventiveTask::class);
    }

    /**
     * Get all logs for this work order.
     */
    public function logs(): HasMany
    {
        return $this->hasMany(WorkOrderLog::class);
    }

    /**
     * Get all attachments for this work order.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Check if work order is overdue.
     */
    public function isOverdue(): bool
    {
        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return false;
        }
        
        if (!$this->sla_deadline) {
            return false;
        }
        
        return $this->sla_deadline->isPast();
    }

    /**
     * Get hours remaining until SLA deadline.
     */
    public function getSlaHoursRemainingAttribute(): ?float
    {
        if (!$this->sla_deadline) {
            return null;
        }
        
        $remaining = $this->sla_deadline->diffInMinutes(now()) / 60;
        return max(0, round($remaining, 2));
    }

    /**
     * Get resolution time in hours.
     */
    public function getResolutionTimeHoursAttribute(): ?float
    {
        if (!$this->created_at || !$this->resolved_at) {
            return null;
        }
        
        return round($this->created_at->diffInMinutes($this->resolved_at) / 60, 2);
    }

    /**
     * Check if work order can be assigned to technician.
     */
    public function canBeAssignedTo(Technician $technician): bool
    {
        return $technician->canAcceptMoreWork();
    }

    /**
     * Add a log entry to this work order.
     */
    public function addLog(string $action, string $notes = null, $userId = null): WorkOrderLog
    {
        return $this->logs()->create([
            'action' => $action,
            'notes' => $notes,
            'user_id' => $userId,
        ]);
    }

    /**
     * Update status and add log.
     */
    public function updateStatus(string $newStatus, string $notes = null, $userId = null): void
    {
        $oldStatus = $this->status;
        $this->status = $newStatus;
        
        if ($newStatus === self::STATUS_IN_PROGRESS && !$this->started_at) {
            $this->started_at = now();
        }
        
        $this->save();
        
        $this->addLog("Status changed from {$oldStatus} to {$newStatus}", $notes, $userId);
    }

    /**
     * Scope to get open work orders.
     */
    public function scopeOpen($query)
    {
        return $query->where('status', self::STATUS_OPEN);
    }

    /**
     * Scope to get overdue work orders.
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_CLOSED)
            ->where('sla_deadline', '<', now());
    }

    /**
     * Scope to get work orders by priority.
     */
    public function scopeByPriority($query, string $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope to get emergency work orders.
     */
    public function scopeEmergency($query)
    {
        return $query->where('type', self::TYPE_EMERGENCY);
    }
}
