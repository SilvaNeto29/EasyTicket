<?php

use App\Actions\Tickets\UpdateTicketStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Project $project;
    public ?string $filterPriority = null;

    public function mount(Project $project): void
    {
        abort_if($project->user_id !== auth()->id(), 403);
        $this->project = $project;
    }

    #[Computed]
    public function groupedTickets(): Collection
    {
        $query = $this->project->tickets()->ordered();

        if ($this->filterPriority) {
            $query->where('priority', $this->filterPriority);
        }

        $tickets = $query->get();

        return collect(TicketStatus::cases())->mapWithKeys(fn ($status) => [
            $status->value => $tickets->filter(fn ($t) => $t->status === $status)->values(),
        ]);
    }

    public function setFilter(?string $priority): void
    {
        $this->filterPriority = $priority;
        unset($this->groupedTickets);
    }

    public function updateTicketStatus(int $ticketId, string $newStatus): void
    {
        $ticket = Ticket::find($ticketId);

        if (! $ticket || $ticket->user_id !== auth()->id()) {
            abort(403);
        }

        try {
            $status = TicketStatus::from($newStatus);
        } catch (\ValueError) {
            $this->addError('status', "Invalid status: {$newStatus}");
            return;
        }

        (new UpdateTicketStatus)->handle($ticket, $status);
        unset($this->groupedTickets);
    }
}; ?>

<div class="py-4" x-data>
    <div class="max-w-full px-4 sm:px-6 lg:px-8 space-y-4">

        {{-- Header --}}
        <div class="flex flex-wrap items-center gap-3 justify-between">
            <div class="flex items-center gap-3 min-w-0">
                <a href="{{ route('projects.index') }}" wire:navigate
                   class="text-gray-400 hover:text-gray-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                @if ($project->color)
                    <div class="w-4 h-4 rounded-full flex-shrink-0"
                         style="background-color: {{ $project->color }}"></div>
                @endif
                <h1 class="text-xl font-bold text-gray-900 truncate">{{ $project->name }}</h1>
            </div>
            <div class="flex items-center gap-2 flex-shrink-0">
                <a href="{{ route('projects.edit', $project) }}" wire:navigate
                   class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                    Edit
                </a>
                <a href="{{ route('tickets.create', ['project_id' => $project->id]) }}" wire:navigate
                   class="px-3 py-1.5 text-sm text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    + Ticket
                </a>
            </div>
        </div>

        {{-- Priority filter --}}
        <div class="flex gap-2 flex-wrap">
            <button wire:click="setFilter(null)"
                    class="px-3 py-1 text-xs rounded-full border transition
                        {{ $filterPriority === null ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-500' }}">
                All
            </button>
            @foreach (App\Enums\TicketPriority::cases() as $p)
                <button wire:click="setFilter('{{ $p->value }}')"
                        class="px-3 py-1 text-xs rounded-full border transition
                            {{ $filterPriority === $p->value ? 'bg-gray-900 text-white border-gray-900' : 'bg-white text-gray-600 border-gray-300 hover:border-gray-500' }}">
                    {{ $p->label() }}
                </button>
            @endforeach
        </div>

        {{-- Kanban Board --}}
        <div class="flex flex-col gap-4 lg:flex-row lg:overflow-x-auto lg:pb-4" id="kanban-board">
            @foreach (App\Enums\TicketStatus::cases() as $status)
                @php $colTickets = $this->groupedTickets[$status->value] @endphp
                <div class="flex-none w-full lg:w-72 xl:w-80">
                    <div class="bg-gray-50 rounded-xl border border-gray-200 overflow-hidden">
                        {{-- Column Header --}}
                        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between bg-white">
                            <div class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full"
                                      style="background-color: {{ $status->color() }}"></span>
                                <span class="text-sm font-semibold text-gray-800">{{ $status->label() }}</span>
                            </div>
                            <span class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full">
                                {{ $colTickets->count() }}
                            </span>
                        </div>

                        {{-- Ticket Cards --}}
                        <div class="p-2 space-y-2 min-h-[120px] sortable-column"
                             data-status="{{ $status->value }}"
                             id="col-{{ $status->value }}">
                            @foreach ($colTickets as $ticket)
                                <div class="bg-white rounded-lg border border-gray-200 p-3 shadow-sm
                                            hover:shadow-md transition cursor-grab active:cursor-grabbing
                                            {{ $ticket->is_overdue ? 'border-l-4 border-l-red-500' : '' }}"
                                     data-ticket-id="{{ $ticket->id }}">

                                    <div class="flex items-start justify-between gap-2 mb-2">
                                        <a href="{{ route('tickets.show', $ticket) }}" wire:navigate
                                           class="text-sm font-medium text-gray-900 hover:text-indigo-700 line-clamp-2 leading-snug">
                                            {{ $ticket->title }}
                                        </a>
                                        @if ($ticket->is_overdue)
                                            <span class="flex-shrink-0 text-xs font-bold text-red-600">!</span>
                                        @endif
                                    </div>

                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="text-xs font-medium px-2 py-0.5 rounded-full
                                            {{ match($ticket->priority) {
                                                TicketPriority::Critical => 'bg-red-100 text-red-700',
                                                TicketPriority::High     => 'bg-orange-100 text-orange-700',
                                                TicketPriority::Medium   => 'bg-yellow-100 text-yellow-700',
                                                TicketPriority::Low      => 'bg-gray-100 text-gray-600',
                                            } }}">
                                            {{ $ticket->priority->label() }}
                                        </span>
                                        @if ($ticket->due_date)
                                            <span class="text-xs text-gray-400">
                                                {{ $ticket->due_date->format('M j') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endforeach

                            @if ($colTickets->isEmpty())
                                <div class="h-16 rounded-lg border-2 border-dashed border-gray-200 flex items-center justify-center">
                                    <span class="text-xs text-gray-400">Drop here</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
