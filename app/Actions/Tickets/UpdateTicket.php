<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;

class UpdateTicket
{
    public function handle(Ticket $ticket, array $data): Ticket
    {
        $ticket->update([
            'title'       => $data['title'],
            'description' => $data['description'] ?? null,
            'priority'    => $data['priority'],
            'due_date'    => $data['due_date'] ?? null,
        ]);

        return $ticket->fresh();
    }
}
