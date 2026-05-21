<?php

use App\Mcp\Servers\EasyTicketServer;
use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\UpdateProjectTool;
use App\Models\Project;
use App\Models\User;

// ── list_projects ─────────────────────────────────────────────────────────────

it('list_projects returns only the authenticated user projects', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Project::factory()->create(['user_id' => $user->id, 'name' => 'My Project']);
    Project::factory()->create(['user_id' => $other->id, 'name' => 'Other Project']);

    EasyTicketServer::actingAs($user)
        ->tool(ListProjectsTool::class)
        ->assertOk()
        ->assertSee('My Project')
        ->assertDontSee('Other Project');
});

it('list_projects returns empty array when user has no projects', function () {
    $user = User::factory()->create();

    $response = EasyTicketServer::actingAs($user)->tool(ListProjectsTool::class);

    $response->assertOk()->assertSee('[]');
});

// ── create_project ────────────────────────────────────────────────────────────

it('create_project creates a project for the authenticated user', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(CreateProjectTool::class, ['name' => 'New Project'])
        ->assertOk()
        ->assertSee('New Project');

    $this->assertDatabaseHas('projects', ['user_id' => $user->id, 'name' => 'New Project']);
});

it('create_project with valid name appears in subsequent list_projects', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(CreateProjectTool::class, ['name' => 'Alpha Project'])
        ->assertOk();

    EasyTicketServer::actingAs($user)
        ->tool(ListProjectsTool::class)
        ->assertOk()
        ->assertSee('Alpha Project');
});

it('create_project with empty name returns validation error', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(CreateProjectTool::class, ['name' => ''])
        ->assertHasErrors();
});

it('create_project with name shorter than 3 chars returns validation error', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(CreateProjectTool::class, ['name' => 'AB'])
        ->assertHasErrors();
});

it('create_project without name returns validation error', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(CreateProjectTool::class, [])
        ->assertHasErrors();
});

it('create_project with invalid color format returns validation error', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(CreateProjectTool::class, ['name' => 'Good Name', 'color' => 'notacolor'])
        ->assertHasErrors();
});

// ── update_project ────────────────────────────────────────────────────────────

it('update_project updates valid fields and persists changes', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Old Name']);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateProjectTool::class, ['project_id' => $project->id, 'name' => 'New Name'])
        ->assertOk()
        ->assertSee('New Name');

    $this->assertDatabaseHas('projects', ['id' => $project->id, 'name' => 'New Name']);
});

it('update_project with another user project_id returns not-found error', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);

    EasyTicketServer::actingAs($user)
        ->tool(UpdateProjectTool::class, ['project_id' => $project->id, 'name' => 'Hacked'])
        ->assertHasErrors(['not found']);
});

it('update_project with non-existent project_id returns not-found error', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(UpdateProjectTool::class, ['project_id' => 99999, 'name' => 'Ghost'])
        ->assertHasErrors(['not found']);
});

// ── delete_project ────────────────────────────────────────────────────────────

it('delete_project removes the project and all its tickets from the database', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id]);
    $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'Ticket A',
        'priority' => 'medium',
        'status'   => 'backlog',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(DeleteProjectTool::class, ['project_id' => $project->id])
        ->assertOk()
        ->assertSee('"deleted":true');

    $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    $this->assertDatabaseMissing('tickets', ['project_id' => $project->id]);
});

it('delete_project with another user project_id returns not-found error', function () {
    $user    = User::factory()->create();
    $other   = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $other->id]);

    EasyTicketServer::actingAs($user)
        ->tool(DeleteProjectTool::class, ['project_id' => $project->id])
        ->assertHasErrors(['not found']);

    $this->assertDatabaseHas('projects', ['id' => $project->id]);
});
