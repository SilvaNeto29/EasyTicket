<?php

namespace App\Enums;

enum TicketPriority: string
{
    case Low      = 'low';
    case Medium   = 'medium';
    case High     = 'high';
    case Critical = 'critical';

    public function sortOrder(): int
    {
        return match($this) {
            self::Critical => 0,
            self::High     => 1,
            self::Medium   => 2,
            self::Low      => 3,
        };
    }

    public function label(): string
    {
        return match($this) {
            self::Low      => 'Low',
            self::Medium   => 'Medium',
            self::High     => 'High',
            self::Critical => 'Critical',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Low      => 'slate',
            self::Medium   => 'blue',
            self::High     => 'amber',
            self::Critical => 'red',
        };
    }
}
