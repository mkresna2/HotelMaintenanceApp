<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PmChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'preventive_task_id',
        'task_template_id',
        'item_order',
        'description',
        'is_required',
        'is_completed',
        'completed_at',
        'completed_by',
        'notes',
        'photo_path',
    ];

    protected $casts = [
        'item_order' => 'integer',
        'is_required' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function preventiveTask(): BelongsTo
    {
        return $this->belongsTo(PreventiveTask::class);
    }

    public function completedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function markComplete($userId = null, string $notes = null): void
    {
        $this->is_completed = true;
        $this->completed_at = now();
        $this->completed_by = $userId;
        if ($notes) {
            $this->notes = $notes;
        }
        $this->save();
    }

    public function markIncomplete(): void
    {
        $this->is_completed = false;
        $this->completed_at = null;
        $this->completed_by = null;
        $this->save();
    }
}
