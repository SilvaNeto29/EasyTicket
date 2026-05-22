<?php

namespace App\Mcp\Tools;

use App\Actions\Tickets\UpdateTicket;
use App\Enums\TicketPriority;
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

#[Name('update_ticket')]
#[Description('Update one or more fields of a ticket. Fields not provided remain unchanged.')]
class UpdateTicketTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'ticket_id'   => ['required', 'integer'],
                'title'       => ['sometimes', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string'],
                'priority'    => ['nullable', 'string', 'in:low,medium,high,critical'],
                'due_date'    => ['nullable', 'date'],
            ]);

            $ticket = Ticket::where('id', $validated['ticket_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $all = $request->all();

            $ticket = app(UpdateTicket::class)->handle($ticket, [
                'title'       => array_key_exists('title', $all) ? $all['title'] : $ticket->title,
                'description' => array_key_exists('description', $all) ? $all['description'] : $ticket->description,
                'priority'    => array_key_exists('priority', $all)
                    ? TicketPriority::from($all['priority'])
                    : $ticket->priority,
                'due_date'    => array_key_exists('due_date', $all)
                    ? ($all['due_date'] ? \Illuminate\Support\Carbon::parse($all['due_date']) : null)
                    : $ticket->due_date,
            ]);

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
            'ticket_id'   => $schema->integer()->required(),
            'title'       => $schema->string()->min(3)->max(255)->nullable(),
            'description' => $schema->string()->nullable(),
            'priority'    => $schema->string()->enum(TicketPriority::class)->nullable(),
            'due_date'    => $schema->string()->format('date')->nullable(),
        ];
    }
}
