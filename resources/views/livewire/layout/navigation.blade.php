<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    public function logout(Logout $logout): void
    {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<div x-data="{ mobileOpen: false }">

    {{-- ===== DESKTOP SIDEBAR (fixed) ===== --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-64 lg:flex-col bg-gray-900">

        <div class="flex h-16 shrink-0 items-center gap-3 border-b border-gray-800 px-6">
            <x-application-logo class="h-8 w-8 text-indigo-400" />
            <span class="text-lg font-semibold text-white tracking-tight">EasyTicket</span>
        </div>

        <nav class="flex flex-1 flex-col gap-y-1 overflow-y-auto px-3 py-4">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            <a href="{{ route('projects.index') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('projects.*') || request()->routeIs('tickets.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                Projects
            </a>
        </nav>

        <div class="shrink-0 border-t border-gray-800 px-3 py-3 space-y-1">
            <a href="{{ route('profile') }}" wire:navigate
               class="flex items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-800">
                <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-sm font-semibold text-white">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                    <p class="truncate text-xs text-gray-400">{{ auth()->user()->email }}</p>
                </div>
            </a>
            <button wire:click="logout"
                    class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
                Log Out
            </button>
        </div>
    </aside>

    {{-- Spacer so content shifts right on desktop --}}
    <div class="hidden lg:block lg:w-64 lg:shrink-0"></div>

    {{-- ===== MOBILE TOP BAR ===== --}}
    <div class="lg:hidden flex items-center gap-3 h-16 bg-gray-900 px-4 border-b border-gray-800">
        <button @click="mobileOpen = true"
                class="rounded-md p-1.5 text-gray-300 hover:bg-gray-800 hover:text-white transition-colors">
            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
            </svg>
        </button>
        <x-application-logo class="h-7 w-7 text-indigo-400" />
        <span class="text-base font-semibold text-white">EasyTicket</span>
    </div>

    {{-- ===== MOBILE SLIDE-OVER ===== --}}
    <div x-show="mobileOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-50 lg:hidden"
         style="display: none;">

        <div @click="mobileOpen = false" class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

        <div x-show="mobileOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="-translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="-translate-x-full"
             class="absolute inset-y-0 left-0 flex w-64 flex-col bg-gray-900">

            <div class="flex h-16 items-center justify-between border-b border-gray-800 px-6">
                <div class="flex items-center gap-3">
                    <x-application-logo class="h-8 w-8 text-indigo-400" />
                    <span class="text-lg font-semibold text-white">EasyTicket</span>
                </div>
                <button @click="mobileOpen = false" class="rounded-md p-1 text-gray-400 hover:text-white transition-colors">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <nav class="flex flex-1 flex-col gap-y-1 overflow-y-auto px-3 py-4">
                <a href="{{ route('dashboard') }}" wire:navigate @click="mobileOpen = false"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                          {{ request()->routeIs('dashboard') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('projects.index') }}" wire:navigate @click="mobileOpen = false"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors
                          {{ request()->routeIs('projects.*') || request()->routeIs('tickets.*') ? 'bg-indigo-600 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                    Projects
                </a>
            </nav>

            <div class="shrink-0 border-t border-gray-800 px-3 py-3 space-y-1">
                <a href="{{ route('profile') }}" wire:navigate @click="mobileOpen = false"
                   class="flex items-center gap-3 rounded-lg px-3 py-2 transition-colors hover:bg-gray-800">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-500 text-sm font-semibold text-white">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium text-white">{{ auth()->user()->name }}</p>
                        <p class="truncate text-xs text-gray-400">Profile & Settings</p>
                    </div>
                </a>
                <button wire:click="logout"
                        class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm text-gray-400 hover:bg-gray-800 hover:text-white transition-colors">
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Log Out
                </button>
            </div>
        </div>
    </div>

</div>
