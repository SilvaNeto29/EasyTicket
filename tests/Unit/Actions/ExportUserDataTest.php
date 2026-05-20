<?php

use App\Actions\Export\ExportUserData;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

it('exports all projects belonging to the user', function () {
    $user = User::factory()->create();
    Project::factory()->count(3)->for($user)->create();

    $data = (new ExportUserData)->handle($user);

    expect($data)->toHaveCount(3);
});

it('includes all tickets within each project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->count(4)->for($project)->for($user)->create();

    $data = (new ExportUserData)->handle($user);

    expect($data[0]['tickets'])->toHaveCount(4);
});

it('includes all required project fields', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();

    $data = (new ExportUserData)->handle($user);

    $p = $data[0];
    expect($p)->toHaveKeys(['id', 'name', 'description', 'color', 'created_at', 'updated_at', 'tickets']);
});

it('includes all required ticket fields', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->for($project)->for($user)->create();

    $data = (new ExportUserData)->handle($user);

    $t = $data[0]['tickets'][0];
    expect($t)->toHaveKeys(['id', 'title', 'description', 'status', 'priority', 'due_date', 'created_at', 'updated_at']);
});

it('does not include other users projects', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($user)->create(['name' => 'Mine']);
    Project::factory()->for($other)->create(['name' => 'Not Mine']);

    $data = (new ExportUserData)->handle($user);

    $names = array_column($data, 'name');
    expect($names)->toContain('Mine');
    expect($names)->not->toContain('Not Mine');
});

it('returns an empty array when user has no projects', function () {
    $user = User::factory()->create();

    $data = (new ExportUserData)->handle($user);

    expect($data)->toBeArray()->toBeEmpty();
});

it('exports status and priority as string values', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->for($project)->for($user)->create([
        'status'   => TicketStatus::InProgress,
        'priority' => TicketPriority::High,
    ]);

    $data = (new ExportUserData)->handle($user);
    $t    = $data[0]['tickets'][0];

    expect($t['status'])->toBe('in_progress');
    expect($t['priority'])->toBe('high');
});
