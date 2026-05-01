<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    const TYPE_WORK_ORDER = 'work_order';
    const TYPE_COMPLAINT = 'complaint';
    const TYPE_SCHEDULE = 'schedule';
    const TYPE_ASSET = 'asset';
    const TYPE_INVENTORY = 'inventory';
    const TYPE_SYSTEM = 'system';

    const CHANNEL_PUSH = 'push';
    const CHANNEL_EMAIL = 'email';
    const CHANNEL_SMS = 'sms';

    protected $fillable = [
        'notifiable_type',
        'notifiable_id',
        'type',
        'title',
        'message',
        'data',
        'channel',
        'priority',
        'is_read',
        'read_at',
        'sent_at',
        'delivered_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'sent_at' => 'datetime',
        'delivered_at' => 'datetime',
    ];

    public function notifiable(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function markAsRead(): void
    {
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
    }

    public function markAsUnread(): void
    {
        $this->is_read = false;
        $this->read_at = null;
        $this->save();
    }

    public function isUrgent(): bool
    {
        return in_array($this->priority, ['critical', 'high']);
    }

    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeUrgent($query)
    {
        return $query->whereIn('priority', ['critical', 'high']);
    }
}
