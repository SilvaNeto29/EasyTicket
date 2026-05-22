<?php

namespace App\Mcp\Tools;

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('create_ticket')]
#[Description('Create a new ticket within a project owned by the authenticated user.')]
class CreateTicketTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'project_id'  => ['required', 'integer'],
                'title'       => ['required', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string'],
                'priority'    => ['nullable', 'string', 'in:low,medium,high,critical'],
                'status'      => ['nullable', 'string', 'in:backlog,todo,in_progress,in_review,done,cancelled'],
                'due_date'    => ['nullable', 'date'],
            ]);

            $project = Project::where('id', $validated['project_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $ticket = app(CreateTicket::class)->handle(
                $project,
                $user,
                $validated['title'],
                $validated['description'] ?? null,
                TicketPriority::from($validated['priority'] ?? 'medium'),
                TicketStatus::from($validated['status'] ?? 'backlog'),
                isset($validated['due_date']) ? Carbon::parse($validated['due_date']) : null,
            );

            return Response::json($ticket->toArray());
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id'  => $schema->integer()->required(),
            'title'       => $schema->string()->min(3)->max(255)->required(),
            'description' => $schema->string()->nullable(),
            'priority'    => $schema->string()->enum(TicketPriority::class)->nullable(),
            'status'      => $schema->string()->enum(TicketStatus::class)->nullable(),
            'due_date'    => $schema->string()->format('date')->nullable(),
        ];
    }
}
