<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'uploaded_by',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'disk',
        'description',
        'attachment_type',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function getUrlAttribute(): ?string
    {
        if (!$this->file_path) {
            return null;
        }
        
        $disk = $this->disk ?? 'public';
        return Storage::disk($disk)->url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    public function deleteFile(): void
    {
        if ($this->file_path) {
            $disk = $this->disk ?? 'public';
            Storage::disk($disk)->delete($this->file_path);
        }
    }

    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($attachment) {
            $attachment->deleteFile();
        });
    }
}
