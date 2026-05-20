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
            <h1 class="text-xl font-bold text-gray-900 mb-6">New Ticket</h1>

            <form wire:submit="save" class="space-y-5">
                {{-- Project selector if not set --}}
                @if (! $projectId)
                    <div>
                        <x-input-label for="projectId" value="Project" />
                        <select id="projectId" wire:model="projectId"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <option value="0">Select a project…</option>
                            @foreach (auth()->user()->projects()->orderBy('name')->get() as $project)
                                <option value="{{ $project->id }}">{{ $project->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Title --}}
                <div>
                    <x-input-label for="title" value="Title" />
                    <x-text-input id="title" wire:model="title" type="text" class="mt-1 block w-full"
                                  placeholder="Brief description of the issue" autofocus />
                    <x-input-error :messages="$errors->get('title')" class="mt-2" />
                </div>

                {{-- Description --}}
                <div>
                    <x-input-label for="description" value="Description (optional)" />
                    <textarea id="description" wire:model="description"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                              rows="4" placeholder="Steps to reproduce, context, etc."></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    {{-- Priority --}}
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

                    {{-- Status --}}
                    <div>
                        <x-input-label for="status" value="Status" />
                        <select id="status" wire:model="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            @foreach (App\Enums\TicketStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <x-input-error :messages="$errors->get('status')" class="mt-2" />
                    </div>
                </div>

                {{-- Due Date --}}
                <div>
                    <x-input-label for="dueDate" value="Due Date (optional)" />
                    <x-text-input id="dueDate" wire:model="dueDate" type="date" class="mt-1 block w-full" />
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
