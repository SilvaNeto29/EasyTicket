<?php

use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('updates ticket status to a valid value', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Backlog,
    ]);
    $this->actingAs($user);

    Volt::test('projects.show', ['project' => $project])
        ->call('updateTicketStatus', $ticket->id, TicketStatus::InProgress->value)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', [
        'id'     => $ticket->id,
        'status' => 'in_progress',
    ]);
});

it('updates updated_at timestamp on status change', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Todo,
    ]);
    $this->actingAs($user);

    $originalUpdatedAt = $ticket->updated_at;
    $this->travel(2)->seconds();

    Volt::test('projects.show', ['project' => $project])
        ->call('updateTicketStatus', $ticket->id, TicketStatus::Done->value)
        ->assertHasNoErrors();

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'done']);
    $refreshed = $ticket->fresh();
    expect($refreshed->updated_at->gt($originalUpdatedAt))->toBeTrue();
});

it('rejects an invalid status string', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.show', ['project' => $project])
        ->call('updateTicketStatus', $ticket->id, 'super-done')
        ->assertHasErrors();
});

it('prevents changing status of another users ticket', function () {
    $owner    = User::factory()->create();
    $intruder = User::factory()->create();
    $ownerProject    = Project::factory()->for($owner)->create();
    $intruderProject = Project::factory()->for($intruder)->create();
    $ticket   = Ticket::factory()->for($ownerProject)->for($owner)->create([
        'status' => TicketStatus::Backlog,
    ]);
    $this->actingAs($intruder);

    Volt::test('projects.show', ['project' => $intruderProject])
        ->call('updateTicketStatus', $ticket->id, TicketStatus::Done->value)
        ->assertForbidden();

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'backlog']);
});

it('redirects unauthenticated access to kanban board', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project))->assertRedirect('/login');
});
