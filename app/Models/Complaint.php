<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Complaint extends Model
{
    use HasFactory;

    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESOLVED = 'resolved';
    const STATUS_CLOSED = 'closed';
    const STATUS_ESCALATED = 'escalated';

    const PRIORITY_URGENT = 'urgent';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_LOW = 'low';

    protected $fillable = [
        'complaint_number',
        'guest_name',
        'guest_email',
        'guest_phone',
        'room_number',
        'floor',
        'category',
        'subcategory',
        'description',
        'priority',
        'status',
        'source',
        'created_by',
        'assigned_to',
        'work_order_id',
        'reported_at',
        'acknowledged_at',
        'resolved_at',
        'closed_at',
        'sla_deadline',
        'escalation_count',
        'satisfaction_rating',
        'satisfaction_notes',
        'compensation_applied',
        'requires_followup',
        'metadata',
    ];

    protected $casts = [
        'reported_at' => 'datetime',
        'acknowledged_at' => 'datetime',
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
        'sla_deadline' => 'datetime',
        'escalation_count' => 'integer',
        'satisfaction_rating' => 'integer',
        'compensation_applied' => 'boolean',
        'requires_followup' => 'boolean',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($complaint) {
            if (!$complaint->complaint_number) {
                $complaint->complaint_number = 'CMP-' . strtoupper(uniqid());
            }
            if (!$complaint->sla_deadline) {
                $complaint->sla_deadline = now()->addHours(2);
            }
            if (!$complaint->reported_at) {
                $complaint->reported_at = now();
            }
        });
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'assigned_to');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function followUps(): HasMany
    {
        return $this->hasMany(ComplaintFollowUp::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, [self::STATUS_RESOLVED, self::STATUS_CLOSED])) {
            return false;
        }
        return $this->sla_deadline && $this->sla_deadline->isPast();
    }

    public function escalate(): void
    {
        $this->status = self::STATUS_ESCALATED;
        $this->escalation_count = ($this->escalation_count ?? 0) + 1;
        $this->save();
    }

    public function resolve(string $notes = null): void
    {
        $this->status = self::STATUS_RESOLVED;
        $this->resolved_at = now();
        if ($notes) {
            $this->metadata = array_merge($this->metadata ?? [], ['resolution_notes' => $notes]);
        }
        $this->save();
    }

    public function scopeNew($query)
    {
        return $query->where('status', self::STATUS_NEW);
    }

    public function scopeEscalated($query)
    {
        return $query->where('status', self::STATUS_ESCALATED);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_CLOSED)
            ->where('sla_deadline', '<', now());
    }
}
