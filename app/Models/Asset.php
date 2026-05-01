<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;

class Asset extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'asset_category_id',
        'name',
        'code',
        'serial_number',
        'model',
        'manufacturer',
        'location_type',
        'location_id',
        'floor',
        'room_number',
        'install_date',
        'purchase_date',
        'warranty_expiry_date',
        'expected_lifetime_years',
        'replacement_cost',
        'vendor_id',
        'status',
        'health_score',
        'last_service_date',
        'next_service_date',
        'service_interval_days',
        'is_critical',
        'qr_code',
        'barcode',
        'metadata',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'install_date' => 'date',
        'purchase_date' => 'date',
        'warranty_expiry_date' => 'date',
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'expected_lifetime_years' => 'integer',
        'replacement_cost' => 'decimal:2',
        'is_critical' => 'boolean',
        'health_score' => 'integer',
        'service_interval_days' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($asset) {
            if (!$asset->health_score) {
                $asset->health_score = $asset->calculateHealthScore();
            }
        });
    }

    /**
     * Get the category of the asset.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(AssetCategory::class, 'asset_category_id');
    }

    /**
     * Get the vendor of the asset.
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Get all work orders for this asset.
     */
    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    /**
     * Get all maintenance schedules for this asset.
     */
    public function maintenanceSchedules(): HasMany
    {
        return $this->hasMany(MaintenanceSchedule::class);
    }

    /**
     * Get all preventive tasks for this asset.
     */
    public function preventiveTasks(): HasMany
    {
        return $this->hasMany(PreventiveTask::class);
    }

    /**
     * Get all documents for this asset.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(AssetDocument::class);
    }

    /**
     * Get all attachments for this asset.
     */
    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    /**
     * Get spare parts associated with this asset.
     */
    public function spareParts(): HasMany
    {
        return $this->hasMany(SparePart::class);
    }

    /**
     * Calculate health score based on age, failures, and PM compliance.
     */
    public function calculateHealthScore(): int
    {
        $score = 100;
        
        // Age factor (reduce score based on age vs expected lifetime)
        if ($this->install_date && $this->expected_lifetime_years) {
            $ageInYears = $this->install_date->diffInYears(now());
            $ageFactor = ($ageInYears / $this->expected_lifetime_years) * 30;
            $score -= min($ageFactor, 30);
        }
        
        // Failure history factor
        $failureCount = $this->workOrders()
            ->whereIn('type', ['corrective', 'emergency'])
            ->where('created_at', '>=', now()->subMonths(6))
            ->count();
        $score -= min($failureCount * 5, 30);
        
        // PM compliance factor
        $overduePM = $this->preventiveTasks()
            ->where('status', 'pending')
            ->where('due_date', '<', now())
            ->count();
        $score -= min($overduePM * 10, 40);
        
        return max(0, min(100, (int) $score));
    }

    /**
     * Update health score.
     */
    public function updateHealthScore(): void
    {
        $this->health_score = $this->calculateHealthScore();
        $this->save();
    }

    /**
     * Check if warranty is expired.
     */
    public function isWarrantyExpired(): bool
    {
        if (!$this->warranty_expiry_date) {
            return true;
        }
        return $this->warranty_expiry_date->isPast();
    }

    /**
     * Get days until warranty expires.
     */
    public function getDaysUntilWarrantyExpiryAttribute(): ?int
    {
        if (!$this->warranty_expiry_date) {
            return null;
        }
        return max(0, $this->warranty_expiry_date->diffInDays(now()));
    }

    /**
     * Check if service is due.
     */
    public function isServiceDue(): bool
    {
        if (!$this->next_service_date) {
            return false;
        }
        return $this->next_service_date->isPast() || $this->next_service_date->eq(now());
    }

    /**
     * Get total maintenance cost for this asset.
     */
    public function getTotalMaintenanceCostAttribute(): float
    {
        return $this->workOrders()
            ->selectRaw('COALESCE(SUM(labor_cost + parts_cost), 0) as total')
            ->value('total') ?? 0.0;
    }

    /**
     * Get failure frequency (failures per year).
     */
    public function getFailureFrequencyAttribute(): float
    {
        $failures = $this->workOrders()
            ->whereIn('type', ['corrective', 'emergency'])
            ->count();
            
        if (!$this->install_date) {
            return 0.0;
        }
        
        $years = max(1, $this->install_date->diffInYears(now()));
        return round($failures / $years, 2);
    }

    /**
     * Scope to get critical assets.
     */
    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }

    /**
     * Scope to get assets with low health score.
     */
    public function scopeLowHealth($query, $threshold = 50)
    {
        return $query->where('health_score', '<=', $threshold);
    }

    /**
     * Scope to get assets needing service.
     */
    public function scopeNeedsService($query)
    {
        return $query->where('next_service_date', '<=', now());
    }
}
