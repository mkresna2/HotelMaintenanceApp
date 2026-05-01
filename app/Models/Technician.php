<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Technician extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'user_id',
        'department_id',
        'employee_id',
        'specialization',
        'skills',
        'certifications',
        'phone',
        'is_available',
        'current_shift',
        'max_concurrent_tasks',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'skills' => 'array',
        'certifications' => 'array',
        'is_available' => 'boolean',
        'max_concurrent_tasks' => 'integer',
    ];

    /**
     * Get the user associated with the technician.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the department of the technician.
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Get all work orders assigned to this technician.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class, 'assigned_to');
    }

    /**
     * Get active work orders count.
     */
    public function getActiveWorkOrdersCountAttribute(): int
    {
        return $this->workOrders()
            ->whereIn('status', ['open', 'in_progress', 'pending_parts'])
            ->count();
    }

    /**
     * Check if technician can accept more work orders.
     */
    public function canAcceptMoreWork(): bool
    {
        if (!$this->is_available) {
            return false;
        }
        
        $activeCount = $this->active_work_orders_count;
        return $activeCount < ($this->max_concurrent_tasks ?? 5);
    }

    /**
     * Get completion rate for this technician.
     */
    public function getCompletionRateAttribute(): float
    {
        $total = $this->workOrders()->count();
        if ($total === 0) {
            return 0.0;
        }
        
        $completed = $this->workOrders()
            ->whereIn('status', ['resolved', 'closed'])
            ->count();
            
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get average resolution time in hours.
     */
    public function getAverageResolutionTimeAttribute(): ?float
    {
        $completed = $this->workOrders()
            ->whereNotNull('resolved_at')
            ->whereNotNull('created_at')
            ->get();
            
        if ($completed->isEmpty()) {
            return null;
        }
        
        $totalHours = $completed->sum(function ($wo) {
            return $wo->created_at->diffInHours($wo->resolved_at);
        });
        
        return round($totalHours / $completed->count(), 2);
    }

    /**
     * Check if technician has specific skill.
     */
    public function hasSkill(string $skill): bool
    {
        $skills = $this->skills ?? [];
        return in_array(strtolower($skill), array_map('strtolower', $skills));
    }
}
