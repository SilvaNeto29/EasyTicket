<?php

namespace App\Models;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'user_id',
        'title',
        'description',
        'status',
        'priority',
        'due_date',
    ];

    protected $casts = [
        'status'   => TicketStatus::class,
        'priority' => TicketPriority::class,
        'due_date' => 'date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIsOverdueAttribute(): bool
    {
        if ($this->due_date === null) {
            return false;
        }

        if ($this->status instanceof TicketStatus && $this->status->isTerminal()) {
            return false;
        }

        return Carbon::parse($this->due_date)->startOfDay()->isPast()
            && ! Carbon::parse($this->due_date)->isToday();
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE priority
                WHEN 'critical' THEN 0
                WHEN 'high'     THEN 1
                WHEN 'medium'   THEN 2
                WHEN 'low'      THEN 3
                ELSE 4
            END ASC,
            CASE WHEN due_date IS NULL THEN 1 ELSE 0 END ASC,
            due_date ASC
        ");
    }

    public function scopeOverdue(Builder $query): Builder
    {
        return $query
            ->whereNotNull('due_date')
            ->where('due_date', '<', now()->startOfDay()->toDateString())
            ->whereNotIn('status', [TicketStatus::Done->value, TicketStatus::Cancelled->value]);
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [TicketStatus::Done->value, TicketStatus::Cancelled->value]);
    }
}
