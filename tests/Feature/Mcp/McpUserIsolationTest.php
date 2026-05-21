<?php

use App\Mcp\Servers\EasyTicketServer;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\DeleteTicketTool;
use App\Mcp\Tools\ExportDataTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\UpdateProjectTool;
use App\Mcp\Tools\UpdateTicketTool;
use App\Models\Project;
use App\Models\User;

it('list_tickets with user A token cannot access user B project tickets', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userB->id]);
    $project->tickets()->create([
        'user_id'  => $userB->id,
        'title'    => 'Secret B Ticket',
        'priority' => 'high',
        'status'   => 'todo',
    ]);

    EasyTicketServer::actingAs($userA)
        ->tool(ListTicketsTool::class, ['project_id' => $project->id])
        ->assertHasErrors(['not found']);
});

it('update_project with user A token cannot modify user B project', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userB->id, 'name' => 'B Project']);

    EasyTicketServer::actingAs($userA)
        ->tool(UpdateProjectTool::class, [
            'project_id' => $project->id,
            'name'       => 'Hijacked',
        ])
        ->assertHasErrors(['not found']);

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'B Project']);
});

it('delete_project with user A token cannot delete user B project', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userB->id]);

    EasyTicketServer::actingAs($userA)
        ->tool(DeleteProjectTool::class, ['project_id' => $project->id])
        ->assertHasErrors(['not found']);

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});

it('update_ticket with user A token cannot modify user B ticket', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userB->id]);
    $ticket  = $project->tickets()->create([
        'user_id'  => $userB->id,
        'title'    => 'B Original',
        'priority' => 'low',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($userA)
        ->tool(UpdateTicketTool::class, [
            'ticket_id' => $ticket->id,
            'title'     => 'Hijacked',
        ])
        ->assertHasErrors(['not found']);

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id, 'title' => 'B Original']);
});

it('delete_ticket with user A token cannot delete user B ticket and ticket still exists', function () {
    $userA   = User::factory()->create();
    $userB   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $userB->id]);
    $ticket  = $project->tickets()->create([
        'user_id'  => $userB->id,
        'title'    => 'Protected',
        'priority' => 'high',
        'status'   => 'todo',
    ]);

    EasyTicketServer::actingAs($userA)
        ->tool(DeleteTicketTool::class, ['ticket_id' => $ticket->id])
        ->assertHasErrors(['not found']);

    $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
});

it('list_projects with user A token never includes user B projects', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Project::factory()->create(['user_id' => $userA->id, 'name' => 'A Project']);
    Project::factory()->create(['user_id' => $userB->id, 'name' => 'B Secret Project']);

    EasyTicketServer::actingAs($userA)
        ->tool(ListProjectsTool::class)
        ->assertOk()
        ->assertSee('A Project')
        ->assertDontSee('B Secret Project');
});

it('export_data with user A token never includes user B data', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();

    Project::factory()->create(['user_id' => $userA->id, 'name' => 'A Project']);
    $bProject = Project::factory()->create(['user_id' => $userB->id, 'name' => 'B Confidential']);
    $bProject->tickets()->create([
        'user_id'  => $userB->id,
        'title'    => 'B Secret Ticket',
        'priority' => 'critical',
        'status'   => 'in_progress',
    ]);

    EasyTicketServer::actingAs($userA)
        ->tool(ExportDataTool::class)
        ->assertOk()
        ->assertSee('A Project')
        ->assertDontSee('B Confidential')
        ->assertDontSee('B Secret Ticket');
});
