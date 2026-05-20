<?php

use App\Enums\TicketPriority;

it('has all four priority cases', function () {
    expect(TicketPriority::cases())->toHaveCount(4);
});

it('assigns critical the lowest sort order (0)', function () {
    expect(TicketPriority::Critical->sortOrder())->toBe(0);
});

it('assigns high sort order 1', function () {
    expect(TicketPriority::High->sortOrder())->toBe(1);
});

it('assigns medium sort order 2', function () {
    expect(TicketPriority::Medium->sortOrder())->toBe(2);
});

it('assigns low the highest sort order (3)', function () {
    expect(TicketPriority::Low->sortOrder())->toBe(3);
});

it('produces correct Critical→High→Medium→Low sort sequence', function () {
    $priorities = [TicketPriority::Low, TicketPriority::Critical, TicketPriority::Medium, TicketPriority::High];
    usort($priorities, fn($a, $b) => $a->sortOrder() <=> $b->sortOrder());

    expect($priorities[0])->toBe(TicketPriority::Critical);
    expect($priorities[1])->toBe(TicketPriority::High);
    expect($priorities[2])->toBe(TicketPriority::Medium);
    expect($priorities[3])->toBe(TicketPriority::Low);
});

it('has correct string values', function () {
    expect(TicketPriority::Low->value)->toBe('low');
    expect(TicketPriority::Medium->value)->toBe('medium');
    expect(TicketPriority::High->value)->toBe('high');
    expect(TicketPriority::Critical->value)->toBe('critical');
});
