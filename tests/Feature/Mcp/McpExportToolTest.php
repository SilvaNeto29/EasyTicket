<?php

use App\Mcp\Servers\EasyTicketServer;
use App\Mcp\Tools\ExportDataTool;
use App\Models\Project;
use App\Models\User;

it('export_data returns a parseable JSON structure of projects with tickets', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'Export Project']);
    $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'Export Ticket',
        'priority' => 'medium',
        'status'   => 'todo',
    ]);

    $response = EasyTicketServer::actingAs($user)->tool(ExportDataTool::class);

    $response->assertOk()->assertSee('Export Project')->assertSee('Export Ticket');
});

it('export_data with no projects returns empty array', function () {
    $user = User::factory()->create();

    EasyTicketServer::actingAs($user)
        ->tool(ExportDataTool::class)
        ->assertOk()
        ->assertSee('[]');
});

it('export_data does not include other users projects or tickets', function () {
    $user  = User::factory()->create();
    $other = User::factory()->create();

    Project::factory()->create(['user_id' => $other->id, 'name' => 'Other User Project']);

    EasyTicketServer::actingAs($user)
        ->tool(ExportDataTool::class)
        ->assertOk()
        ->assertDontSee('Other User Project');
});

it('export_data structure matches the browser export format with expected keys', function () {
    $user    = User::factory()->create();
    $project = Project::factory()->create(['user_id' => $user->id, 'name' => 'My Project']);
    $project->tickets()->create([
        'user_id'  => $user->id,
        'title'    => 'My Ticket',
        'priority' => 'high',
        'status'   => 'in_progress',
    ]);

    EasyTicketServer::actingAs($user)
        ->tool(ExportDataTool::class)
        ->assertOk()
        ->assertSee('My Project')
        ->assertSee('My Ticket')
        ->assertSee('"tickets"')
        ->assertSee('"description"')
        ->assertSee('"color"')
        ->assertSee('"status"')
        ->assertSee('"priority"');
});
