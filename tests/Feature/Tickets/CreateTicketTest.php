<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('creates a ticket with valid data', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'Fix the login bug')
        ->set('description', 'Users cannot log in')
        ->set('priority', TicketPriority::High->value)
        ->set('status', TicketStatus::Todo->value)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', [
        'project_id' => $project->id,
        'user_id'    => $user->id,
        'title'      => 'Fix the login bug',
        'priority'   => 'high',
        'status'     => 'todo',
    ]);
});

it('rejects an empty title', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', '')
        ->set('priority', TicketPriority::Medium->value)
        ->set('status', TicketStatus::Backlog->value)
        ->call('save')
        ->assertHasErrors(['title']);
});

it('rejects a title shorter than 3 characters', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'AB')
        ->set('priority', TicketPriority::Medium->value)
        ->set('status', TicketStatus::Backlog->value)
        ->call('save')
        ->assertHasErrors(['title']);
});

it('rejects a title longer than 255 characters', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', str_repeat('T', 256))
        ->set('priority', TicketPriority::Medium->value)
        ->set('status', TicketStatus::Backlog->value)
        ->call('save')
        ->assertHasErrors(['title']);
});

it('rejects an invalid priority value', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'Valid Title')
        ->set('priority', 'super-ultra-high')
        ->set('status', TicketStatus::Backlog->value)
        ->call('save')
        ->assertHasErrors(['priority']);
});

it('rejects an invalid status value', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'Valid Title')
        ->set('priority', TicketPriority::Medium->value)
        ->set('status', 'flying')
        ->call('save')
        ->assertHasErrors(['status']);
});

it('prevents creating a ticket in another users project', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $project = Project::factory()->for($other)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'Sneaky Ticket')
        ->set('priority', TicketPriority::Low->value)
        ->set('status', TicketStatus::Backlog->value)
        ->call('save')
        ->assertForbidden();

    $this->assertDatabaseCount('tickets', 0);
});

it('allows a null due date', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'No Deadline')
        ->set('priority', TicketPriority::Low->value)
        ->set('status', TicketStatus::Backlog->value)
        ->set('dueDate', null)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', ['due_date' => null]);
});

it('marks a past due date ticket as overdue', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.create')
        ->set('projectId', $project->id)
        ->set('title', 'Late Ticket')
        ->set('priority', TicketPriority::High->value)
        ->set('status', TicketStatus::Todo->value)
        ->set('dueDate', now()->subDays(3)->toDateString())
        ->call('save')
        ->assertHasNoErrors();

    $ticket = Ticket::where('title', 'Late Ticket')->first();
    expect($ticket->is_overdue)->toBeTrue();
});

it('redirects unauthenticated users to login', function () {
    $this->get('/tickets/create')->assertRedirect('/login');
});
