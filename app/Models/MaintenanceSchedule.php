<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceSchedule extends Model
{
    use HasFactory;

    const FREQUENCY_DAILY = 'daily';
    const FREQUENCY_WEEKLY = 'weekly';
    const FREQUENCY_MONTHLY = 'monthly';
    const FREQUENCY_QUARTERLY = 'quarterly';
    const FREQUENCY_ANNUALLY = 'annually';
    const FREQUENCY_CUSTOM = 'custom';

    protected $fillable = [
        'name',
        'description',
        'asset_id',
        'asset_category_id',
        'department_id',
        'frequency',
        'frequency_interval',
        'day_of_week',
        'day_of_month',
        'month_of_year',
        'start_date',
        'end_date',
        'next_due_date',
        'time_of_day',
        'estimated_duration_minutes',
        'assigned_to',
        'priority',
        'is_active',
        'auto_generate_wo',
        'generate_days_before',
        'last_generated_at',
        'metadata',
    ];

    protected $casts = [
        'frequency_interval' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'next_due_date' => 'date',
        'time_of_day' => 'datetime:H:i',
        'estimated_duration_minutes' => 'integer',
        'is_active' => 'boolean',
        'auto_generate_wo' => 'boolean',
        'generate_days_before' => 'integer',
        'last_generated_at' => 'datetime',
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

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function assignedTechnician(): BelongsTo
    {
        return $this->belongsTo(Technician::class, 'assigned_to');
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function isDue(): bool
    {
        if (!$this->is_active || !$this->next_due_date) {
            return false;
        }
        return $this->next_due_date <= now();
    }

    public function calculateNextDueDate(): ?\DateTime
    {
        if (!$this->start_date) {
            return null;
        }

        $current = $this->next_due_date ?? $this->start_date;

        switch ($this->frequency) {
            case self::FREQUENCY_DAILY:
                return $current->addDays($this->frequency_interval ?? 1);
            case self::FREQUENCY_WEEKLY:
                return $current->addWeeks($this->frequency_interval ?? 1);
            case self::FREQUENCY_MONTHLY:
                return $current->addMonths($this->frequency_interval ?? 1);
            case self::FREQUENCY_QUARTERLY:
                return $current->addMonths(3 * ($this->frequency_interval ?? 1));
            case self::FREQUENCY_ANNUALLY:
                return $current->addYears($this->frequency_interval ?? 1);
            default:
                return null;
        }
    }

    public function updateNextDueDate(): void
    {
        $nextDate = $this->calculateNextDueDate();
        if ($nextDate) {
            $this->next_due_date = $nextDate;
            $this->save();
        }
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('is_active', true)
            ->where('next_due_date', '<=', now());
    }

    public function scopeUpcoming($query, $days = 7)
    {
        return $query->where('is_active', true)
            ->whereBetween('next_due_date', [now(), now()->addDays($days)]);
    }
}
