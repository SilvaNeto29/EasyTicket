<?php

use App\Enums\TicketStatus;

it('has all six status cases', function () {
    expect(TicketStatus::cases())->toHaveCount(6);
});

it('marks done as terminal', function () {
    expect(TicketStatus::Done->isTerminal())->toBeTrue();
});

it('marks cancelled as terminal', function () {
    expect(TicketStatus::Cancelled->isTerminal())->toBeTrue();
});

it('marks active statuses as non-terminal', function () {
    foreach ([TicketStatus::Backlog, TicketStatus::Todo, TicketStatus::InProgress, TicketStatus::InReview] as $status) {
        expect($status->isTerminal())->toBeFalse("Expected {$status->value} to be non-terminal");
    }
});

it('has non-empty labels for all cases', function () {
    foreach (TicketStatus::cases() as $status) {
        expect($status->label())->toBeString()->not->toBeEmpty();
    }
});

it('has correct string values', function () {
    expect(TicketStatus::Backlog->value)->toBe('backlog');
    expect(TicketStatus::Todo->value)->toBe('todo');
    expect(TicketStatus::InProgress->value)->toBe('in_progress');
    expect(TicketStatus::InReview->value)->toBe('in_review');
    expect(TicketStatus::Done->value)->toBe('done');
    expect(TicketStatus::Cancelled->value)->toBe('cancelled');
});
