<?php

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('deletes a ticket from the database', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $ticket  = Ticket::factory()->for($project)->for($user)->create();
    $this->actingAs($user);

    Volt::test('tickets.show', ['ticket' => $ticket])
        ->call('delete')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
});

it('prevents deleting another users ticket', function () {
    $owner    = User::factory()->create();
    $intruder = User::factory()->create();
    $project  = Project::factory()->for($owner)->create();
    $ticket   = Ticket::factory()->for($project)->for($owner)->create();

    $this->actingAs($intruder)
        ->get(route('tickets.show', $ticket))
        ->assertForbidden();

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
});

it('returns 404 for a nonexistent ticket', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/tickets/999999')
        ->assertNotFound();
});

it('redirects unauthenticated users to login', function () {
    $ticket = Ticket::factory()->create();

    $this->get(route('tickets.show', $ticket))->assertRedirect('/login');
});
