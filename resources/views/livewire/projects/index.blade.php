<?php

use App\Actions\Projects\DeleteProject;
use App\Models\Project;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public ?int $confirmingDeleteId = null;

    public function with(): array
    {
        $projects = auth()->user()->projects()
            ->withCount([
                'tickets as total_tickets_count',
                'tickets as open_tickets_count'    => fn ($q) => $q->open(),
                'tickets as overdue_tickets_count' => fn ($q) => $q->overdue(),
            ])
            ->orderBy('created_at')
            ->get();

        return compact('projects');
    }

    public function confirmDelete(int $projectId): void
    {
        $this->confirmingDeleteId = $projectId;
    }

    public function cancelDelete(): void
    {
        $this->confirmingDeleteId = null;
    }

    public function deleteProject(int $projectId): void
    {
        $project = Project::find($projectId);

        if (! $project || $project->user_id !== auth()->id()) {
            $this->confirmingDeleteId = null;
            abort(403);
        }

        (new DeleteProject)->handle($project);
        $this->confirmingDeleteId = null;
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
            <a href="{{ route('projects.create') }}" wire:navigate
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                New Project
            </a>
        </div>

        @if ($projects->isEmpty())
            <div class="text-center py-20 bg-white rounded-xl border-2 border-dashed border-gray-200">
                <svg class="mx-auto w-14 h-14 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                </svg>
                <p class="text-gray-500 font-medium text-lg">No projects yet</p>
                <p class="text-gray-400 text-sm mt-1">Get started by creating your first project.</p>
                <a href="{{ route('projects.create') }}" wire:navigate
                   class="mt-4 inline-block px-5 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    Create Project
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach ($projects as $project)
                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden hover:shadow-md transition group">
                        {{-- Color bar --}}
                        @if ($project->color)
                            <div class="h-1.5" style="background-color: {{ $project->color }}"></div>
                        @endif

                        <div class="p-5">
                            <div class="flex items-start justify-between gap-2 mb-3">
                                <a href="{{ route('projects.show', $project) }}" wire:navigate
                                   class="font-semibold text-gray-900 group-hover:text-indigo-700 line-clamp-2 leading-snug text-base">
                                    {{ $project->name }}
                                </a>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <a href="{{ route('projects.edit', $project) }}" wire:navigate
                                       class="p-1.5 text-gray-400 hover:text-indigo-600 rounded-md hover:bg-indigo-50 transition"
                                       title="Edit">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </a>
                                    <button wire:click="confirmDelete({{ $project->id }})"
                                            class="p-1.5 text-gray-400 hover:text-red-600 rounded-md hover:bg-red-50 transition"
                                            title="Delete">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            @if ($project->description)
                                <p class="text-sm text-gray-500 line-clamp-2 mb-3">{{ $project->description }}</p>
                            @endif

                            <div class="flex gap-4 text-sm text-gray-600 border-t border-gray-100 pt-3 mt-auto">
                                <span title="Total tickets">
                                    <span class="font-semibold text-gray-900">{{ $project->total_tickets_count }}</span> total
                                </span>
                                <span title="Open tickets">
                                    <span class="font-semibold text-blue-600">{{ $project->open_tickets_count }}</span> open
                                </span>
                                @if ($project->overdue_tickets_count > 0)
                                    <span title="Overdue tickets">
                                        <span class="font-semibold text-red-600">{{ $project->overdue_tickets_count }}</span> overdue
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Delete Confirmation Modal --}}
        @if ($confirmingDeleteId)
            <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
                 wire:click.self="cancelDelete">
                <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Delete project?</h3>
                    <p class="text-sm text-gray-600 mb-6">
                        This will permanently delete the project and <strong>all its tickets</strong>. This cannot be undone.
                    </p>
                    <div class="flex justify-end gap-3">
                        <button wire:click="cancelDelete"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                            Cancel
                        </button>
                        <button wire:click="deleteProject({{ $confirmingDeleteId }})"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
