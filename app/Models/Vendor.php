<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'contact_person',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'zip_code',
        'country',
        'service_types',
        'contract_start_date',
        'contract_end_date',
        'contract_value',
        'payment_terms',
        'is_active',
        'rating',
        'notes',
    ];

    protected $casts = [
        'service_types' => 'array',
        'contract_start_date' => 'date',
        'contract_end_date' => 'date',
        'contract_value' => 'decimal:2',
        'is_active' => 'boolean',
        'rating' => 'decimal:1',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function workOrders(): HasMany
    {
        return $this->hasMany(WorkOrder::class);
    }

    public function isContractExpiringSoon($days = 30): bool
    {
        if (!$this->contract_end_date) {
            return false;
        }
        return $this->contract_end_date->diffInDays(now()) <= $days;
    }

    public function getContractStatusAttribute(): string
    {
        if (!$this->contract_end_date) {
            return 'no_contract';
        }
        
        if ($this->contract_end_date->isPast()) {
            return 'expired';
        }
        
        if ($this->isContractExpiringSoon(30)) {
            return 'expiring_soon';
        }
        
        return 'active';
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeExpiringContracts($query, $days = 30)
    {
        return $query->where('is_active', true)
            ->whereNotNull('contract_end_date')
            ->whereBetween('contract_end_date', [now(), now()->addDays($days)]);
    }
}
