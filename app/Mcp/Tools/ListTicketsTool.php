<?php

namespace App\Mcp\Tools;

use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('list_tickets')]
#[Description('List all tickets for a project belonging to the authenticated user.')]
class ListTicketsTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'project_id' => ['required', 'integer'],
            ]);

            $project = Project::where('id', $validated['project_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $tickets = $project->tickets()
                ->get()
                ->map(fn ($t) => [
                    'id'          => $t->id,
                    'project_id'  => $t->project_id,
                    'title'       => $t->title,
                    'description' => $t->description,
                    'priority'    => $t->priority->value,
                    'status'      => $t->status->value,
                    'due_date'    => $t->due_date?->toDateString(),
                    'is_overdue'  => $t->due_date !== null
                        && $t->due_date->isPast()
                        && ! $t->status->isTerminal(),
                ])
                ->values()
                ->all();

            return Response::json($tickets);
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->required(),
        ];
    }
}
