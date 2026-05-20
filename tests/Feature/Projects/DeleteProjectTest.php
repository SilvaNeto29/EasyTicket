<?php

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('deletes a project with no tickets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.index')
        ->call('deleteProject', $project->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
});

it('cascades deletion to all tickets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $tickets = Ticket::factory()->count(3)->for($project)->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.index')
        ->call('deleteProject', $project->id)
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    foreach ($tickets as $ticket) {
        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
    }
});

it('prevents deleting another users project', function () {
    $owner    = User::factory()->create();
    $intruder = User::factory()->create();
    $project  = Project::factory()->for($owner)->create();
    $this->actingAs($intruder);

    Volt::test('projects.index')
        ->call('deleteProject', $project->id)
        ->assertForbidden();

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

it('redirects unauthenticated users to login on project list', function () {
    $this->get('/projects')->assertRedirect('/login');
});
