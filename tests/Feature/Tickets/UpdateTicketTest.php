<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('updates a ticket with valid data', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create([
        'title'    => 'Old Title',
        'priority' => TicketPriority::Low,
    ]);
    $this->actingAs($user);

    Volt::test('tickets.show', ['ticket' => $ticket])
        ->set('title', 'New Title')
        ->set('priority', TicketPriority::High->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', [
        'id'       => $ticket->id,
        'title'    => 'New Title',
        'priority' => 'high',
    ]);
});

it('rejects an empty title on update', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.show', ['ticket' => $ticket])
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

it('rejects an invalid priority on update', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.show', ['ticket' => $ticket])
        ->set('title', 'Valid Title')
        ->set('priority', 'urgent-ultra')
        ->call('save')
        ->assertHasErrors(['priority']);
});

it('prevents updating another users ticket', function () {
    $owner    = User::factory()->create();
    $intruder = User::factory()->create();
    $project  = Project::factory()->for($owner)->create();
    $ticket   = Ticket::factory()->for($project)->for($owner)->create();

    $this->actingAs($intruder)
        ->get(route('tickets.show', $ticket))
        ->assertForbidden();
});

it('marks a ticket as overdue when due date is set to past', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create([
        'status'   => TicketStatus::Todo,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);
    $this->actingAs($user);

    Volt::test('tickets.show', ['ticket' => $ticket])
        ->set('title', $ticket->title)
        ->set('priority', $ticket->priority->value)
        ->set('dueDate', now()->subDays(2)->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $ticket->refresh();
    expect($ticket->is_overdue)->toBeTrue();
});

it('is not overdue when status is done regardless of past due date', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create([
        'status'   => TicketStatus::Done,
        'due_date' => now()->subDays(5)->toDateString(),
    ]);

    expect($ticket->is_overdue)->toBeFalse();
});

it('redirects unauthenticated users to login', function () {
    $ticket = Ticket::factory()->create();

    $this->get(route('tickets.show', $ticket))->assertRedirect('/login');
});
