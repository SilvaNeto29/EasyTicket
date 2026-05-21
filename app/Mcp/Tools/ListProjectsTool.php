<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list_projects')]
#[Description('List all projects belonging to the authenticated user.')]
class ListProjectsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        $projects = Project::where('user_id', $user->id)
            ->withCount([
                'tickets as total_tickets',
                'tickets as open_tickets' => fn ($q) => $q->whereNotIn('status', ['done', 'cancelled']),
                'tickets as overdue_tickets' => fn ($q) => $q
                    ->whereNotNull('due_date')
                    ->where('due_date', '<', now()->toDateString())
                    ->whereNotIn('status', ['done', 'cancelled']),
            ])
            ->get()
            ->map(fn ($p) => [
                'id'              => $p->id,
                'name'            => $p->name,
                'description'     => $p->description,
                'color'           => $p->color,
                'total_tickets'   => $p->total_tickets,
                'open_tickets'    => $p->open_tickets,
                'overdue_tickets' => $p->overdue_tickets,
            ])
            ->values()
            ->all();

        return Response::json($projects);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
