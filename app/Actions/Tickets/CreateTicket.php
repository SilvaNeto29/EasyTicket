<?php

namespace App\Actions\Tickets;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Carbon;

class CreateTicket
{
    public function handle(
        Project $project,
        User $user,
        string $title,
        ?string $description,
        TicketPriority $priority,
        TicketStatus $status,
        ?Carbon $dueDate
    ): Ticket {
        return $project->tickets()->create([
            'user_id'     => $user->id,
            'title'       => $title,
            'description' => $description,
            'priority'    => $priority,
            'status'      => $status,
            'due_date'    => $dueDate,
        ]);
    }
}
