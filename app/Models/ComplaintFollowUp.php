<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintFollowUp extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'followup_type',
        'method',
        'notes',
        'guest_response',
        'satisfaction_rating',
        'scheduled_at',
        'completed_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'completed_at' => 'datetime',
        'satisfaction_rating' => 'integer',
    ];

    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function markCompleted(string $guestResponse = null, int $rating = null): void
    {
        $this->completed_at = now();
        if ($guestResponse) {
            $this->guest_response = $guestResponse;
        }
        if ($rating) {
            $this->satisfaction_rating = $rating;
        }
        $this->save();
    }

    public function isOverdue(): bool
    {
        return $this->scheduled_at && $this->scheduled_at->isPast() && !$this->completed_at;
    }

    public function scopePending($query)
    {
        return $query->whereNull('completed_at');
    }

    public function scopeOverdue($query)
    {
        return $query->whereNull('completed_at')
            ->where('scheduled_at', '<', now());
    }
}
