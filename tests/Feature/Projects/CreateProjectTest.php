<?php

use App\Models\Project;
use App\Models\User;
use Livewire\Volt\Volt;

it('creates a project with valid data', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', 'My New Project')
        ->set('description', 'A great project')
        ->set('color', '#3B82F6')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('projects.index'));

    $this->assertDatabaseHas('projects', [
        'user_id' => $user->id,
        'name'    => 'My New Project',
        'color'   => '#3B82F6',
    ]);
});

it('creates a project with null description and color', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', 'Minimal Project')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'user_id'     => $user->id,
        'name'        => 'Minimal Project',
        'description' => null,
        'color'       => null,
    ]);
});

it('rejects an empty name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name']);

    $this->assertDatabaseCount('projects', 0);
});

it('rejects a name shorter than 3 characters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', 'AB')
        ->call('save')
        ->assertHasErrors(['name']);
});

it('rejects a name longer than 255 characters', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', str_repeat('A', 256))
        ->call('save')
        ->assertHasErrors(['name']);
});

it('rejects an invalid hex color', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', 'Valid Name')
        ->set('color', 'not-a-color')
        ->call('save')
        ->assertHasErrors(['color']);
});

it('rejects a partial hex color', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', 'Valid Name')
        ->set('color', '#ABC')
        ->call('save')
        ->assertHasErrors(['color']);
});

it('redirects unauthenticated users to login on GET', function () {
    $this->get('/projects/create')->assertRedirect('/login');
});

it('does not create a project for another user', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    $this->actingAs($user);

    Volt::test('projects.create')
        ->set('name', 'User Project')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseMissing('projects', ['user_id' => $other->id]);
});
