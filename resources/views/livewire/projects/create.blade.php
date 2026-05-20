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
            <h1 class="text-xl font-bold text-gray-900 mb-6">New Project</h1>

            <form wire:submit="save" class="space-y-5">
                {{-- Name --}}
                <div>
                    <x-input-label for="name" value="Project Name" />
                    <x-text-input id="name" wire:model="name" type="text" class="mt-1 block w-full"
                                  placeholder="My awesome project" autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                {{-- Description --}}
                <div>
                    <x-input-label for="description" value="Description (optional)" />
                    <textarea id="description" wire:model="description"
                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                              rows="3" placeholder="What is this project about?"></textarea>
                    <x-input-error :messages="$errors->get('description')" class="mt-2" />
                </div>

                {{-- Color --}}
                <div>
                    <x-input-label for="color" value="Color (optional)" />
                    <div class="mt-1 flex items-center gap-3">
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
