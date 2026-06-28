<?php

use App\Actions\Tickets\DeleteTicket;
use App\Actions\Tickets\UpdateTicket;
use App\Actions\Tickets\UpdateTicketStatus;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public Ticket $ticket;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $description = null;

    #[Validate('required|string|in:low,medium,high,critical')]
    public string $priority = 'medium';

    #[Validate('nullable|date_format:Y-m-d')]
    public ?string $dueDate = null;

    public bool $editing       = false;
    public bool $confirmDelete = false;

    public function mount(Ticket $ticket): void
    {
        abort_if($ticket->user_id !== auth()->id(), 403);

        $this->ticket      = $ticket->load('project');
        $this->title       = $ticket->title;
        $this->description = $ticket->description;
        $this->priority    = $ticket->priority->value;
        $this->dueDate     = $ticket->due_date?->toDateString();
    }

    public function save(UpdateTicket $action): void
    {
        $this->validate();

        $this->ticket = $action->handle($this->ticket, [
            'title'       => $this->title,
            'description' => $this->description,
            'priority'    => $this->priority,
            'due_date'    => $this->dueDate,
        ]);

        $this->editing = false;
    }

    public function updateStatus(string $newStatus): void
    {
        try {
            $status = TicketStatus::from($newStatus);
        } catch (\ValueError) {
            $this->addError('status', 'Invalid status.');
            return;
        }

        $this->ticket = (new UpdateTicketStatus)->handle($this->ticket, $status);
    }

    public function delete(DeleteTicket $action): void
    {
        abort_if($this->ticket->user_id !== auth()->id(), 403);

        $projectId = $this->ticket->project_id;
        $action->handle($this->ticket);

        $this->redirectRoute('projects.show', ['project' => $projectId], navigate: true);
    }
}; ?>

<div class="py-6">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <div class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('projects.show', $ticket->project) }}" wire:navigate
               class="hover:text-gray-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                {{ $ticket->project->name }}
            </a>
            <span>/</span>
            <span class="text-gray-700 font-medium truncate">{{ $ticket->title }}</span>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">

            {{-- Header bar --}}
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-wrap">
                    {{-- Priority badge --}}
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full
                        {{ match($ticket->priority) {
                            TicketPriority::Critical => 'bg-red-100 text-red-700',
                            TicketPriority::High     => 'bg-orange-100 text-orange-700',
                            TicketPriority::Medium   => 'bg-yellow-100 text-yellow-700',
                            TicketPriority::Low      => 'bg-gray-100 text-gray-600',
                        } }}">
                        {{ $ticket->priority->label() }}
                    </span>

                    {{-- Overdue badge --}}
                    @if ($ticket->is_overdue)
                        <span class="text-xs font-bold text-white bg-red-600 px-2.5 py-1 rounded-full animate-pulse">
                            Overdue
                        </span>
                    @endif
                </div>

                <div class="flex items-center gap-2 flex-shrink-0">
                    <button wire:click="$set('editing', true)"
                            class="px-3 py-1.5 text-sm text-gray-600 border border-gray-300 rounded-lg hover:bg-gray-50 transition
                                   {{ $editing ? 'hidden' : '' }}">
                        Edit
                    </button>
                    <button wire:click="$set('confirmDelete', true)"
                            class="px-3 py-1.5 text-sm text-red-600 border border-red-300 rounded-lg hover:bg-red-50 transition">
                        Delete
                    </button>
                </div>
            </div>

            {{-- Main Content --}}
            <div class="p-6 space-y-6">
                @if ($editing)
                    <form wire:submit="save" class="space-y-5">
                        <div>
                            <x-input-label for="title" value="Title" />
                            <x-text-input id="title" wire:model="title" type="text" class="mt-1 block w-full" autofocus />
                            <x-input-error :messages="$errors->get('title')" class="mt-2" />
                        </div>
                        <div>
                            <x-input-label for="description" value="Description" />
                            <textarea id="description" wire:model="description"
                                      class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                      rows="5"></textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="priority" value="Priority" />
                                <select id="priority" wire:model="priority"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                    @foreach (App\Enums\TicketPriority::cases() as $p)
                                        <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                    @endforeach
                                </select>
                                <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="dueDate" value="Due Date" />
                                <x-text-input id="dueDate" wire:model="dueDate" type="date" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('dueDate')" class="mt-2" />
                            </div>
                        </div>
                        <div class="flex justify-end gap-3">
                            <button type="button" wire:click="$set('editing', false)"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Cancel
                            </button>
                            <x-primary-button>Save Changes</x-primary-button>
                        </div>
                    </form>
                @else
                    {{-- View Mode --}}
                    <div>
                        <h1 class="text-xl font-bold text-gray-900 leading-snug mb-3">{{ $ticket->title }}</h1>
                        @if ($ticket->description)
                            <p class="text-gray-700 text-sm whitespace-pre-wrap">{{ $ticket->description }}</p>
                        @else
                            <p class="text-gray-400 text-sm italic">No description provided.</p>
                        @endif
                    </div>

                    {{-- Property rows --}}
                    <div class="border-t border-gray-100 divide-y divide-gray-50">

                        {{-- Status --}}
                        <div class="flex items-center gap-4 py-3">
                            <div class="flex items-center gap-2 w-32 flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Status</span>
                            </div>
                            <select wire:change="updateStatus($event.target.value)"
                                    class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                @foreach (App\Enums\TicketStatus::cases() as $s)
                                    <option value="{{ $s->value }}"
                                        {{ $ticket->status === $s ? 'selected' : '' }}>
                                        {{ $s->label() }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Priority --}}
                        <div class="flex items-center gap-4 py-3">
                            <div class="flex items-center gap-2 w-32 flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                                </svg>
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Priority</span>
                            </div>
                            <span class="text-sm font-medium px-2.5 py-0.5 rounded-full
                                {{ match($ticket->priority) {
                                    TicketPriority::Critical => 'bg-red-100 text-red-700',
                                    TicketPriority::High     => 'bg-orange-100 text-orange-700',
                                    TicketPriority::Medium   => 'bg-yellow-100 text-yellow-700',
                                    TicketPriority::Low      => 'bg-gray-100 text-gray-600',
                                } }}">
                                {{ $ticket->priority->label() }}
                            </span>
                        </div>

                        {{-- Due Date --}}
                        <div class="flex items-center gap-4 py-3">
                            <div class="flex items-center gap-2 w-32 flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Due Date</span>
                            </div>
                            @if ($ticket->due_date)
                                <span class="text-sm {{ $ticket->is_overdue ? 'text-red-600 font-semibold' : 'text-gray-700' }}">
                                    {{ $ticket->due_date->format('M j, Y') }}
                                    @if ($ticket->is_overdue)
                                        <span class="ml-1 text-xs text-red-500">(overdue)</span>
                                    @endif
                                </span>
                            @else
                                <span class="text-sm text-gray-400 italic">No due date</span>
                            @endif
                        </div>

                        {{-- Created / Updated --}}
                        <div class="flex items-center gap-4 py-3">
                            <div class="flex items-center gap-2 w-32 flex-shrink-0">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created</span>
                            </div>
                            <span class="text-sm text-gray-500">
                                {{ $ticket->created_at->diffForHumans() }}
                                <span class="text-gray-400 mx-1">·</span>
                                updated {{ $ticket->updated_at->diffForHumans() }}
                            </span>
                        </div>

                    </div>
                @endif
            </div>
        </div>

        {{-- Delete Confirmation Modal --}}
        @if ($confirmDelete)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                 wire:click.self="$set('confirmDelete', false)">
                <div class="bg-white rounded-xl shadow-xl max-w-sm w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete ticket?</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        "<strong>{{ $ticket->title }}</strong>" will be permanently removed.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="$set('confirmDelete', false)"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button wire:click="delete"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
