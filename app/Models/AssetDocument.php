<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetDocument extends Model
{
    use HasFactory;

    const TYPE_MANUAL = 'manual';
    const TYPE_WARRANTY = 'warranty';
    const TYPE_CERTIFICATE = 'certificate';
    const TYPE_INSPECTION_REPORT = 'inspection_report';
    const TYPE_OTHER = 'other';

    protected $fillable = [
        'asset_id',
        'document_type',
        'title',
        'description',
        'file_name',
        'file_path',
        'file_size',
        'mime_type',
        'uploaded_by',
        'expiry_date',
        'is_confidential',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'expiry_date' => 'date',
        'is_confidential' => 'boolean',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isExpiringSoon($days = 30): bool
    {
        if (!$this->expiry_date) {
            return false;
        }
        return $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function scopeExpiring($query, $days = 30)
    {
        return $query->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [now(), now()->addDays($days)]);
    }
}
