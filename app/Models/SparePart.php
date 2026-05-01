<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'part_number',
        'sku',
        'asset_id',
        'category',
        'description',
        'quantity_on_hand',
        'minimum_stock_level',
        'maximum_stock_level',
        'unit_cost',
        'reorder_quantity',
        'supplier_name',
        'supplier_contact',
        'location',
        'last_ordered_date',
        'last_received_date',
        'is_critical',
        'metadata',
    ];

    protected $casts = [
        'quantity_on_hand' => 'integer',
        'minimum_stock_level' => 'integer',
        'maximum_stock_level' => 'integer',
        'unit_cost' => 'decimal:2',
        'reorder_quantity' => 'integer',
        'last_ordered_date' => 'date',
        'last_received_date' => 'date',
        'is_critical' => 'boolean',
        'metadata' => 'array',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function isLowStock(): bool
    {
        return $this->quantity_on_hand <= $this->minimum_stock_level;
    }

    public function isOutOfStock(): bool
    {
        return $this->quantity_on_hand <= 0;
    }

    public function getTotalValueAttribute(): float
    {
        return $this->quantity_on_hand * $this->unit_cost;
    }

    public function adjustQuantity(int $adjustment, string $reason = null): void
    {
        $this->quantity_on_hand += $adjustment;
        $this->quantity_on_hand = max(0, $this->quantity_on_hand);
        
        if ($adjustment > 0) {
            $this->last_received_date = now();
        }
        
        $this->save();
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('quantity_on_hand', '<=', 'minimum_stock_level');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('quantity_on_hand', 0);
    }

    public function scopeCritical($query)
    {
        return $query->where('is_critical', true);
    }
}
