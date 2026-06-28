<?php

use App\Actions\Projects\CreateProject;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    #[Validate('required|string|min:3|max:255')]
    public string $name = '';

    #[Validate('nullable|string|max:5000')]
    public ?string $description = null;

    #[Validate('nullable|string|regex:/^#[0-9A-Fa-f]{6}$/')]
    public ?string $color = null;

    public function save(CreateProject $action): void
    {
        $this->validate();

        $action->handle(
            auth()->user(),
            $this->name,
            $this->description ?: null,
            $this->color ?: null,
        );

        $this->redirectRoute('projects.index', navigate: true);
    }
}; ?>

<div class="py-6">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="mb-6">
            <a href="{{ route('projects.index') }}" wire:navigate
               class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Back to Projects
            </a>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-6 sm:p-8">
            <div class="mb-6">
                <h1 class="text-xl font-bold text-gray-900">New Project</h1>
                <p class="text-sm text-gray-500 mt-1">Organize your tickets under a project.</p>
            </div>

            <form wire:submit="save" class="space-y-5">
                {{-- Name --}}
                <div>
                    <label for="name" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                        Project Name
                    </label>
                    <x-text-input id="name" wire:model="name" type="text" class="block w-full"
                                  placeholder="My awesome project" autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
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
                              rows="3" placeholder="What is this project about?"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                {{-- Color --}}
                <div>
                    <label for="color" class="flex items-center gap-1.5 text-sm font-medium text-gray-700 mb-1">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                        </svg>
                        Color
                        <span class="text-gray-400 font-normal">(optional)</span>
                    </label>
                    <div class="flex items-center gap-3">
                        <input id="color" wire:model="color" type="color"
                               class="h-10 w-16 rounded-md border border-gray-300 cursor-pointer p-0.5"
                               value="#6366f1" />
                        <x-text-input wire:model="color" type="text"
                                      class="block w-full"
                                      placeholder="#6366f1" maxlength="7" />
                    </div>
                    <x-input-error :messages="$errors->get('color')" class="mt-2" />
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('projects.index') }}" wire:navigate
                       class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <x-primary-button>
                        Create Project
                    </x-primary-button>
                </div>
            </form>
        </div>
    </div>
</div>
