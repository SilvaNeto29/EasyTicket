<?php

namespace App\Actions\Tickets;

use App\Models\Ticket;

class DeleteTicket
{
    public function handle(Ticket $ticket): void
    {
        $ticket->delete();
    }
}
