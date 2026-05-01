<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreventiveTask extends Model
{
    use HasFactory;

    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'asset_id',
        'asset_category_id',
        'maintenance_schedule_id',
        'task_template_id',
        'title',
        'description',
        'checklist_items',
        'status',
        'priority',
        'assigned_to',
        'due_date',
        'started_at',
        'completed_at',
        'completed_by',
        'work_order_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'checklist_items' => 'array',
        'due_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(MaintenanceSchedule::class, 'maintenance_schedule_id');
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'assigned_to');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function workOrder(): BelongsTo
    {
        return $this->belongsTo(WorkOrder::class);
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(PmChecklistItem::class);
    }

    public function isOverdue(): bool
    {
        if (in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_SKIPPED])) {
            return false;
        }
        return $this->due_date && $this->due_date < now();
    }

    public function getCompletionPercentageAttribute(): float
    {
        $total = $this->checklistItems()->count();
        if ($total === 0) {
            return $this->status === self::STATUS_COMPLETED ? 100 : 0;
        }
        
        $completed = $this->checklistItems()
            ->where('is_completed', true)
            ->count();
            
        return round(($completed / $total) * 100, 2);
    }

    public function complete(array $checklistResults = [], string $notes = null, $userId = null): void
    {
        $this->status = self::STATUS_COMPLETED;
        $this->completed_at = now();
        $this->completed_by = $userId;
        
        if ($notes) {
            $this->notes = $notes;
        }
        
        $this->save();
        
        foreach ($checklistResults as $itemId => $result) {
            $item = $this->checklistItems()->find($itemId);
            if ($item) {
                $item->update([
                    'is_completed' => $result['completed'] ?? false,
                    'notes' => $result['notes'] ?? null,
                ]);
            }
        }
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->where('due_date', '<', now());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('status', self::STATUS_PENDING)
            ->whereBetween('due_date', [now(), now()->addDays($days)]);
    }
}
