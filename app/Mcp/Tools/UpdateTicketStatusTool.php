<?php

namespace App\Mcp\Tools;

use App\Actions\Tickets\UpdateTicketStatus;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('update_ticket_status')]
#[Description('Move a ticket to a new status column.')]
class UpdateTicketStatusTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'ticket_id'  => ['required', 'integer'],
                'new_status' => ['required', 'string', 'in:backlog,todo,in_progress,in_review,done,cancelled'],
            ]);

            $ticket = Ticket::where('id', $validated['ticket_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $ticket = app(UpdateTicketStatus::class)->handle(
                $ticket,
                TicketStatus::from($validated['new_status']),
            );

            return Response::json($ticket->toArray());
        } catch (ModelNotFoundException) {
            return Response::error('Ticket not found.');
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id'  => $schema->integer()->required(),
            'new_status' => $schema->string()->enum(TicketStatus::class)->required(),
        ];
    }
}
