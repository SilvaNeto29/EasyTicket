<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('loads the dashboard for an authenticated user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

it('shows all user projects with correct names', function () {
    $user = User::factory()->create();
    $p1   = Project::factory()->for($user)->create(['name' => 'Alpha']);
    $p2   = Project::factory()->for($user)->create(['name' => 'Beta']);
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertSee('Alpha')
        ->assertSee('Beta');
});

it('shows overdue tickets in the attention section', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->overdue()->for($project)->for($user)->create([
        'title' => 'Overdue Issue',
    ]);
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertSee('Overdue Issue');
});

it('shows critical priority tickets in the attention section', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->for($project)->for($user)->create([
        'title'    => 'Critical Thing',
        'priority' => TicketPriority::Critical,
        'status'   => TicketStatus::Todo,
        'due_date' => null,
    ]);
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertSee('Critical Thing');
});

it('does not show done tickets in the attention section', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->for($project)->for($user)->create([
        'title'    => 'Completed Ticket',
        'status'   => TicketStatus::Done,
        'priority' => TicketPriority::Critical,
        'due_date' => now()->subDays(1)->toDateString(),
    ]);
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertDontSee('Completed Ticket');
});

it('does not show cancelled tickets in the attention section', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    Ticket::factory()->for($project)->for($user)->create([
        'title'    => 'Cancelled Ticket',
        'status'   => TicketStatus::Cancelled,
        'priority' => TicketPriority::Critical,
        'due_date' => now()->subDays(1)->toDateString(),
    ]);
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertDontSee('Cancelled Ticket');
});

it('only shows the current users own projects', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();
    Project::factory()->for($other)->create(['name' => 'Other Users Project']);
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertDontSee('Other Users Project');
});

it('shows empty state when user has no projects', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Volt::test('dashboard')
        ->assertSee('No projects');
});

it('redirects unauthenticated users to login', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});
