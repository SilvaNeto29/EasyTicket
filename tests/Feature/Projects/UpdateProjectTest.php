<?php

use App\Models\Project;
use App\Models\User;
use Livewire\Volt\Volt;

it('updates a project with valid data', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'Old Name']);
    $this->actingAs($user);

    Volt::test('projects.edit', ['project' => $project])
        ->set('name', 'New Name')
        ->set('description', 'Updated description')
        ->set('color', '#10B981')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'id'          => $project->id,
        'name'        => 'New Name',
        'description' => 'Updated description',
        'color'       => '#10B981',
    ]);
});

it('rejects an empty name on update', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.edit', ['project' => $project])
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('rejects a name shorter than 3 chars on update', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.edit', ['project' => $project])
        ->set('name', 'AB')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('rejects a name longer than 255 chars on update', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.edit', ['project' => $project])
        ->set('name', str_repeat('Z', 256))
        ->call('save')
        ->assertHasErrors(['name']);
});

it('prevents a user from editing another users project', function () {
    $owner    = User::factory()->create();
    $intruder = User::factory()->create();
    $project  = Project::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->get(route('projects.edit', $project))
        ->assertForbidden();
});

it('returns 404 for a nonexistent project', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/projects/999999/edit')
        ->assertNotFound();
});

it('redirects unauthenticated users to login', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.edit', $project))->assertRedirect('/login');
});
