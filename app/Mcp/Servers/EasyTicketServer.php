<?php

namespace App\Mcp\Servers;

use App\Mcp\Tools\CreateProjectTool;
use App\Mcp\Tools\CreateTicketTool;
use App\Mcp\Tools\DeleteProjectTool;
use App\Mcp\Tools\DeleteTicketTool;
use App\Mcp\Tools\ExportDataTool;
use App\Mcp\Tools\ListProjectsTool;
use App\Mcp\Tools\ListTicketsTool;
use App\Mcp\Tools\UpdateProjectTool;
use App\Mcp\Tools\UpdateTicketStatusTool;
use App\Mcp\Tools\UpdateTicketTool;
use Laravel\Mcp\Server;
use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;

#[Name('Easy Ticket Server')]
#[Version('1.0.0')]
#[Instructions('Manage your projects and tickets via natural language. All operations are scoped to your account only.')]
class EasyTicketServer extends Server
{
    protected array $tools = [
        ListProjectsTool::class,
        CreateProjectTool::class,
        UpdateProjectTool::class,
        DeleteProjectTool::class,
        ListTicketsTool::class,
        CreateTicketTool::class,
        UpdateTicketTool::class,
        UpdateTicketStatusTool::class,
        DeleteTicketTool::class,
        ExportDataTool::class,
    ];

    protected array $resources = [];

    protected array $prompts = [];
}
