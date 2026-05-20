<?php

use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

it('returns JSON with content-disposition attachment header', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/export')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/json')
        ->assertHeader('Content-Disposition');
});

it('includes todays date in the filename', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/export');

    $disposition = $response->headers->get('Content-Disposition');
    expect($disposition)->toContain(now()->format('Y-m-d'));
});

it('exports all user data as valid JSON', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create(['name' => 'My Export Project']);
    Ticket::factory()->count(2)->for($project)->for($user)->create();

    $response = $this->actingAs($user)->get('/export');

    $data = $response->json();

    expect($data)->toBeArray();
    expect($data)->toHaveCount(1);
    expect($data[0]['name'])->toBe('My Export Project');
    expect($data[0]['tickets'])->toHaveCount(2);
});

it('returns an empty JSON array when user has no data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/export');

    expect($response->json())->toBeArray()->toBeEmpty();
});

it('does not include other users data in the export', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($other)->create(['name' => 'Secret Project']);

    $data = $this->actingAs($user)->get('/export')->json();

    $names = array_column($data, 'name');
    expect($names)->not->toContain('Secret Project');
});

it('redirects unauthenticated users to login', function () {
    $this->get('/export')->assertRedirect('/login');
});
