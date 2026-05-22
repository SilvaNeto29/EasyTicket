<?php

use App\Mcp\Servers\EasyTicketServer;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\DeleteTicketTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\UpdateTicketStatusTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;

// ── list_tickets ──────────────────────────────────────────────────────────────

it('list_tickets with own project_id returns all tickets for that project', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'Fix Bug',
        'priority' => 'high',
        'status'   => 'todo',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(ListTicketsTool::class, ['project_id' => $project->id])
        ->assertOk()
        ->assertSee('Fix Bug');
});

it('list_tickets with another user project_id returns not-found error', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);
    $project->tickets()->create([
        'user_id'  => $other->id,
        'title'    => 'Secret Ticket',
        'priority' => 'low',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(ListTicketsTool::class, ['project_id' => $project->id])
        ->assertHasErrors(['not found']);
});

it('list_tickets on project with zero tickets returns empty array', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    EasyTicketServer::actingAs($user)
        ->tool(ListTicketsTool::class, ['project_id' => $project->id])
        ->assertOk()
        ->assertSee('[]');
});

// ── create_ticket ─────────────────────────────────────────────────────────────

it('create_ticket with valid data creates ticket with correct defaults', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    EasyTicketServer::actingAs($user)
        ->tool(CreateTicketTool::class, [
            'project_id' => $project->id,
            'title'      => 'New Feature',
        ])
        ->assertOk()
        ->assertSee('New Feature');

    $this->assertDatabaseHas('tickets', [
        'project_id' => $project->id,
        'title'      => 'New Feature',
        'priority'   => 'medium',
        'status'     => 'backlog',
    ]);
});

it('create_ticket missing title returns validation error', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    EasyTicketServer::actingAs($user)
        ->tool(CreateTicketTool::class, ['project_id' => $project->id])
        ->assertHasErrors();
});

it('create_ticket with title shorter than 3 chars returns validation error', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    EasyTicketServer::actingAs($user)
        ->tool(CreateTicketTool::class, ['project_id' => $project->id, 'title' => 'AB'])
        ->assertHasErrors();
});

it('create_ticket with invalid priority value returns validation error', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);

    EasyTicketServer::actingAs($user)
        ->tool(CreateTicketTool::class, [
            'project_id' => $project->id,
            'title'      => 'Valid Title',
            'priority'   => 'super-urgent',
        ])
        ->assertHasErrors();
});

it('create_ticket with another user project_id returns not-found error', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);

    EasyTicketServer::actingAs($user)
        ->tool(CreateTicketTool::class, [
            'project_id' => $project->id,
            'title'      => 'Sneak Ticket',
        ])
        ->assertHasErrors(['not found']);
});

// ── update_ticket ─────────────────────────────────────────────────────────────

it('update_ticket partial update changes only specified fields', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $ticket  = $project->tickets()->create([
        'user_id'     => $user->id,
        'title'       => 'Original Title',
        'description' => 'Original description',
        'priority'    => 'low',
        'status'      => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'title'     => 'Updated Title',
        ])
        ->assertOk()
        ->assertSee('Updated Title');

    $this->assertDatabaseHas('tickets', [
        'id'          => $ticket->id,
        'title'       => 'Updated Title',
        'description' => 'Original description',
        'priority'    => 'low',
    ]);
});

it('update_ticket with another user ticket_id returns not-found error', function () {
    $user   = User::factory()->create();
    $other  = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);
    $ticket = $project->tickets()->create([
        'user_id'  => $other->id,
        'title'    => 'Other Ticket',
        'priority' => 'low',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'title'     => 'Hacked',
        ])
        ->assertHasErrors(['not found']);
});

// ── update_ticket_status ──────────────────────────────────────────────────────

it('update_ticket_status with valid status updates and persists', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $ticket  = $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'Pending Task',
        'priority' => 'medium',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateTicketStatusTool::class, [
            'ticket_id'  => $ticket->id,
            'new_status' => 'in_progress',
        ])
        ->assertOk()
        ->assertSee('in_progress');

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'status' => 'in_progress']);
});

it('update_ticket_status with invalid status value returns descriptive error', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $ticket  = $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'Some Task',
        'priority' => 'low',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateTicketStatusTool::class, [
            'ticket_id'  => $ticket->id,
            'new_status' => 'flying',
        ])
        ->assertHasErrors();
});

it('update_ticket_status with another user ticket_id returns not-found error', function () {
    $user   = User::factory()->create();
    $other  = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);
    $ticket = $project->tickets()->create([
        'user_id'  => $other->id,
        'title'    => 'Other Task',
        'priority' => 'low',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateTicketStatusTool::class, [
            'ticket_id'  => $ticket->id,
            'new_status' => 'done',
        ])
        ->assertHasErrors(['not found']);
});

// ── delete_ticket ─────────────────────────────────────────────────────────────

it('delete_ticket removes the ticket from the database', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $ticket  = $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'To Be Deleted',
        'priority' => 'low',
        'status'   => 'done',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(DeleteTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertOk()
        ->assertSee('"deleted":true');

    $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
});

it('delete_ticket with another user ticket_id returns not-found error and ticket still exists', function () {
    $user   = User::factory()->create();
    $other  = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);
    $ticket = $project->tickets()->create([
        'user_id'  => $other->id,
        'title'    => 'Protected Ticket',
        'priority' => 'high',
        'status'   => 'todo',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(DeleteTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertHasErrors(['not found']);

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
});
