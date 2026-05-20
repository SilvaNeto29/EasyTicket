<?php

namespace App\Actions\Tickets;

use App\Enums\TicketStatus;
use App\Models\Ticket;

class UpdateTicketStatus
{
    public function handle(Ticket $ticket, TicketStatus $newStatus): Ticket
    {
        $ticket->update(['status' => $newStatus]);

        return $ticket->fresh();
    }
}
