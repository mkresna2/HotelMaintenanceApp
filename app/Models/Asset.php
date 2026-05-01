<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Notifications\Notifiable;

class Asset extends Model
{
    /** @use HasFactory<\Database\Factories\AssetFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'asset_tag',
        'name',
        'description',
        'category_id',
        'location_type',
        'location_identifier',
        'floor',
        'building',
        'latitude',
        'longitude',
        'manufacturer',
        'model',
        'serial_number',
        'install_date',
        'warranty_expiry',
        'vendor_id',
        'purchase_price',
        'expected_lifespan_months',
        'specifications',
        'manuals',
        'status',
        'health_score',
        'last_service_date',
        'next_service_due',
        'total_work_orders',
        'failure_count',
        'total_maintenance_cost',
        'is_critical',
    ];

    protected $casts = [
        'install_date' => 'date',
        'warranty_expiry' => 'date',
        'purchase_price' => 'decimal:2',
        'specifications' => 'array',
        'manuals' => 'array',
        'last_service_date' => 'datetime',
        'next_service_due' => 'datetime',
        'is_critical' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'category_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    public function preventiveMaintenanceTasks(): HasMany
    {
        return $this->hasMany(PreventiveMaintenanceTask::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function isWarrantyExpiringSoon(int $days = 30): bool
    {
        if (!$this->warranty_expiry) {
            return false;
        }
        return $this->warranty_expiry->diffInDays(now()) <= $days;
    }

    public function isServiceDue(): bool
    {
        if (!$this->next_service_due) {
            return false;
        }
        return $this->next_service_due->isPast();
    }
}
