<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Support\Carbon;

it('is overdue when past due date and status is active', function () {
    $ticket = new Ticket([
        'due_date' => Carbon::yesterday(),
        'status' => TicketStatus::InProgress,
    ]);

    expect($ticket->is_overdue)->toBeTrue();
});

it('is overdue when past due date and status is backlog', function () {
    $ticket = new Ticket([
        'due_date' => Carbon::yesterday(),
        'status' => TicketStatus::Backlog,
    ]);

    expect($ticket->is_overdue)->toBeTrue();
});

it('is not overdue when past due date but status is done', function () {
    $ticket = new Ticket([
        'due_date' => Carbon::yesterday(),
        'status' => TicketStatus::Done,
    ]);

    expect($ticket->is_overdue)->toBeFalse();
});

it('is not overdue when past due date but status is cancelled', function () {
    $ticket = new Ticket([
        'due_date' => Carbon::yesterday(),
        'status' => TicketStatus::Cancelled,
    ]);

    expect($ticket->is_overdue)->toBeFalse();
});

it('is not overdue when due date is today', function () {
    $ticket = new Ticket([
        'due_date' => Carbon::today(),
        'status' => TicketStatus::InProgress,
    ]);

    expect($ticket->is_overdue)->toBeFalse();
});

it('is not overdue when due date is in the future', function () {
    $ticket = new Ticket([
        'due_date' => Carbon::tomorrow(),
        'status' => TicketStatus::InProgress,
    ]);

    expect($ticket->is_overdue)->toBeFalse();
});

it('is never overdue when due date is null', function () {
    $ticket = new Ticket([
        'due_date' => null,
        'status' => TicketStatus::InProgress,
    ]);

    expect($ticket->is_overdue)->toBeFalse();
});
