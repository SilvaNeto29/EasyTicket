<?php

use App\Actions\Tickets\CreateTicket;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public int $projectId = 0;

    #[Validate('required|string|min:3|max:255')]
    public string $title = '';

    #[Validate('nullable|string')]
    public ?string $description = null;

    #[Validate('required|string|in:low,medium,high,critical')]
    public string $priority = 'medium';

    #[Validate('required|string|in:backlog,todo,in_progress,in_review,done,cancelled')]
    public string $status = 'backlog';

    #[Validate('nullable|date_format:Y-m-d')]
    public ?string $dueDate = null;

    public function mount(): void
    {
        $projectId = request()->query('project_id');

        if ($projectId) {
            $project = Project::find($projectId);
            if ($project && $project->user_id === auth()->id()) {
                $this->projectId = (int) $projectId;
            }
        }

        $status = request()->query('status');
        if ($status && collect(TicketStatus::cases())->contains(fn ($s) => $s->value === $status)) {
            $this->status = $status;
        }
    }

    public function save(CreateTicket $action): void
    {
        $this->validate();

        $project = Project::find($this->projectId);

        if (! $project || $project->user_id !== auth()->id()) {
            abort(403);
        }

        $action->handle(
            $project,
            auth()->user(),
            $this->title,
            $this->description ?: null,
            TicketPriority::from($this->priority),
            TicketStatus::from($this->status),
            $this->dueDate ? Carbon::parse($this->dueDate) : null,
        );

        $this->redirectRoute('projects.show', ['project' => $project], navigate: true);
    }
}; ?>

<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        @php $backProject = $projectId ? \App\Models\Project::find($projectId) : null; @endphp

        <div class="mb-6">
            @if ($backProject)
                <a href="{{ route('projects.show', $backProject) }}" wire:navigate
                   class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to {{ $backProject->name }}
                </a>
            @else
                <a href="{{ route('projects.index') }}" wire:navigate
                   class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Back to Projects
                </a>
            @endif
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900">New Ticket</h1>
                <p class="text-sm text-gray-500 mt-1">Track a task, bug, or feature request.</p>
            </div>

            <form wire:submit="save" class="space-y-5">
                {{-- Project selector if not set --}}
                @if (! $projectId)
                    <div>
                        <label for="projectId" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                            Project
                        </label>
                        <select id="projectId" wire:model="projectId"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="0">Select a project…</option>
                            @foreach (auth()->user()->projects()->orderBy('name')->get() as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Title --}}
                <div>
                    <label for="title" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Title
                    </label>
                    <x-text-input id="title" wire:model="title" type="text" class="block w-full"
                                  placeholder="Brief description of the issue" autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                {{-- Description --}}
                <div>
                    <label for="description" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M4 6h16M4 10h16M4 14h10"/>
                        </svg>
                        Description
                        <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <textarea id="description" wire:model="description"
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                              rows="4" placeholder="Steps to reproduce, context, etc."></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Priority --}}
                    <div>
                        <label for="priority" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"/>
                            </svg>
                            Priority
                        </label>
                        <select id="priority" wire:model="priority"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach (App\Enums\TicketPriority::cases() as $p)
                                <option value="{{ $p->value }}">{{ $p->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('priority')" class="mt-2" />
                    </div>

                    {{-- Status --}}
                    <div>
                        <label for="status" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Status
                        </label>
                        <select id="status" wire:model="status"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach (App\Enums\TicketStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                {{-- Due Date --}}
                <div>
                    <label for="dueDate" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Due Date
                        <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <x-text-input id="dueDate" wire:model="dueDate" type="date" class="block w-full" />
                    <x-input-error :messages="$errors->get('dueDate')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ $backProject ? route('projects.show', $backProject) : route('projects.index') }}"
                       wire:navigate
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <x-primary-button>
                        Create Ticket
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
