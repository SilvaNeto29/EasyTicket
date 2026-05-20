<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Backlog    = 'backlog';
    case Todo       = 'todo';
    case InProgress = 'in_progress';
    case InReview   = 'in_review';
    case Done       = 'done';
    case Cancelled  = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Backlog    => 'Backlog',
            self::Todo       => 'To Do',
            self::InProgress => 'In Progress',
            self::InReview   => 'In Review',
            self::Done       => 'Done',
            self::Cancelled  => 'Cancelled',
        };
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::Done, self::Cancelled]);
    }

    public function color(): string
    {
        return match($this) {
            self::Backlog    => '#64748b',
            self::Todo       => '#3b82f6',
            self::InProgress => '#f59e0b',
            self::InReview   => '#a855f7',
            self::Done       => '#22c55e',
            self::Cancelled  => '#ef4444',
        };
    }
}
