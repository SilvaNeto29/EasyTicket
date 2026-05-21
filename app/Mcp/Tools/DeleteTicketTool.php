<?php

namespace App\Mcp\Tools;

use App\Actions\Tickets\DeleteTicket;
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

#[Name('delete_ticket')]
#[Description('Permanently delete a ticket. This action is irreversible.')]
class DeleteTicketTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'ticket_id' => ['required', 'integer'],
            ]);

            $ticket = Ticket::where('id', $validated['ticket_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $ticketId = $ticket->id;
            app(DeleteTicket::class)->handle($ticket);

            return Response::json(['deleted' => true, 'ticket_id' => $ticketId]);
        } catch (ModelNotFoundException) {
            return Response::error('Ticket not found.');
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'ticket_id' => $schema->integer()->required(),
        ];
    }
}
