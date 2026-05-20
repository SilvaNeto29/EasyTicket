<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Livewire\Volt\Volt;

it('renders the kanban board with all 6 status columns', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Volt::test('projects.show', ['project' => $project])
        ->assertSee('backlog')
        ->assertSee('todo')
        ->assertSee('in_progress')
        ->assertSee('in_review')
        ->assertSee('done')
        ->assertSee('cancelled');
});

it('shows tickets under their correct status column', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    $backlogTicket = Ticket::factory()->for($project)->for($user)->create([
        'title'  => 'Backlog Task',
        'status' => TicketStatus::Backlog,
    ]);
    $doneTicket = Ticket::factory()->for($project)->for($user)->create([
        'title'  => 'Finished Task',
        'status' => TicketStatus::Done,
    ]);

    $component = Volt::test('projects.show', ['project' => $project]);
    $grouped   = $component->get('groupedTickets');

    expect($grouped[TicketStatus::Backlog->value]->pluck('id'))->toContain($backlogTicket->id);
    expect($grouped[TicketStatus::Done->value]->pluck('id'))->toContain($doneTicket->id);
    expect($grouped[TicketStatus::Backlog->value]->pluck('id'))->not->toContain($doneTicket->id);
});

it('sorts tickets Critical → High → Medium → Low within each column', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Todo, 'priority' => TicketPriority::Low, 'due_date' => null,
    ]);
    Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Todo, 'priority' => TicketPriority::Critical, 'due_date' => null,
    ]);
    Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Todo, 'priority' => TicketPriority::Medium, 'due_date' => null,
    ]);

    $component = Volt::test('projects.show', ['project' => $project]);
    $grouped   = $component->get('groupedTickets');
    $todo      = $grouped[TicketStatus::Todo->value];

    expect($todo->first()->priority)->toBe(TicketPriority::Critical);
    expect($todo->last()->priority)->toBe(TicketPriority::Low);
});

it('sorts tickets with earlier due dates first within same priority', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    $later   = Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::InProgress, 'priority' => TicketPriority::High,
        'due_date' => now()->addDays(10)->toDateString(),
    ]);
    $earlier = Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::InProgress, 'priority' => TicketPriority::High,
        'due_date' => now()->addDays(2)->toDateString(),
    ]);

    $component  = Volt::test('projects.show', ['project' => $project]);
    $grouped    = $component->get('groupedTickets');
    $inProgress = $grouped[TicketStatus::InProgress->value];

    expect($inProgress->first()->id)->toBe($earlier->id);
    expect($inProgress->last()->id)->toBe($later->id);
});

it('places tickets with null due date last within same priority group', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->for($user)->create();
    $this->actingAs($user);

    $withDue = Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Todo, 'priority' => TicketPriority::Medium,
        'due_date' => now()->addDays(5)->toDateString(),
    ]);
    $noDue = Ticket::factory()->for($project)->for($user)->create([
        'status' => TicketStatus::Todo, 'priority' => TicketPriority::Medium, 'due_date' => null,
    ]);

    $component = Volt::test('projects.show', ['project' => $project]);
    $grouped   = $component->get('groupedTickets');
    $todo      = $grouped[TicketStatus::Todo->value];

    expect($todo->first()->id)->toBe($withDue->id);
    expect($todo->last()->id)->toBe($noDue->id);
});

it('prevents viewing another users project board', function () {
    $owner    = User::factory()->create();
    $intruder = User::factory()->create();
    $project  = Project::factory()->for($owner)->create();

    $this->actingAs($intruder)
        ->get(route('projects.show', $project))
        ->assertForbidden();
});

it('redirects unauthenticated users to login', function () {
    $project = Project::factory()->create();

    $this->get(route('projects.show', $project))->assertRedirect('/login');
});
