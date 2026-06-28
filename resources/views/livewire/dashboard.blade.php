<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Project;
use App\Models\Ticket;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        $user = auth()->user();

        $projects = $user->projects()
            ->withCount([
                'tickets as total_tickets_count',
                'tickets as open_tickets_count'  => fn ($q) => $q->open(),
                'tickets as overdue_tickets_count' => fn ($q) => $q->overdue(),
            ])
            ->orderBy('created_at')
            ->get();

        $attentionTickets = Ticket::query()
            ->where('user_id', $user->id)
            ->where(function ($q) {
                $q->overdue()
                  ->orWhere(function ($q2) {
                      $q2->where('priority', TicketPriority::Critical->value)
                         ->whereNotIn('status', [TicketStatus::Done->value, TicketStatus::Cancelled->value]);
                  });
            })
            ->with('project')
            ->orderByRaw("CASE priority WHEN 'critical' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END ASC")
            ->limit(20)
            ->get();

        return compact('projects', 'attentionTickets');
    }
}; ?>

<div class="py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <a href="/export"
               class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                </svg>
                Export JSON
            </a>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $projects->count() }}</p>
                    <p class="text-sm text-gray-500">Projects</p>
                </div>
            </div>

            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-50 text-blue-600 flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold text-gray-900">{{ $projects->sum('open_tickets_count') }}</p>
                    <p class="text-sm text-gray-500">Open Tickets</p>
                </div>
            </div>

            @php $overdueTotal = $projects->sum('overdue_tickets_count'); @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-5 flex items-center gap-4 {{ $overdueTotal > 0 ? 'border-red-200 bg-red-50' : '' }}">
                <div class="flex h-11 w-11 items-center justify-center rounded-xl {{ $overdueTotal > 0 ? 'bg-red-100 text-red-600' : 'bg-gray-100 text-gray-500' }} flex-shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-2xl font-bold {{ $overdueTotal > 0 ? 'text-red-700' : 'text-gray-900' }}">{{ $overdueTotal }}</p>
                    <p class="text-sm {{ $overdueTotal > 0 ? 'text-red-500' : 'text-gray-500' }}">Overdue</p>
                </div>
            </div>
        </div>

        {{-- Attention Required --}}
        @if ($attentionTickets->isNotEmpty())
            <section>
                <h2 class="text-base font-semibold text-red-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                        <path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 1.999-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd"/>
                    </svg>
                    Attention Required
                </h2>
                <div class="bg-red-50 border border-red-100 rounded-xl p-3 space-y-2">
                    @foreach ($attentionTickets as $ticket)
                        <a href="{{ route('tickets.show', $ticket) }}" wire:navigate
                           class="flex items-center gap-3 p-3 bg-white rounded-lg border border-red-200 hover:border-red-400 transition shadow-sm">
                            <span class="w-2 h-2 rounded-full flex-shrink-0
                                {{ $ticket->priority === TicketPriority::Critical ? 'bg-red-600' : 'bg-orange-400' }}">
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ $ticket->title }}</p>
                                <p class="text-xs text-gray-500 truncate">{{ $ticket->project->name }}</p>
                            </div>
                            @if ($ticket->is_overdue)
                                <span class="flex-shrink-0 text-xs font-semibold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-200">
                                    Overdue
                                </span>
                            @endif
                            <span class="flex-shrink-0 text-xs font-medium px-2 py-0.5 rounded-full
                                {{ $ticket->priority === TicketPriority::Critical ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700' }}">
                                {{ $ticket->priority->label() }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Projects Grid --}}
        <section>
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-800">Your Projects</h2>
                <a href="{{ route('projects.create') }}" wire:navigate
                   class="text-sm font-medium text-indigo-600 hover:text-indigo-800">
                    + New Project
                </a>
            </div>

            @if ($projects->isEmpty())
                <div class="text-center py-16 bg-white rounded-xl border-2 border-dashed border-gray-200">
                    <svg class="mx-auto w-12 h-12 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
                    </svg>
                    <p class="text-gray-500 font-medium">No projects yet</p>
                    <a href="{{ route('projects.create') }}" wire:navigate
                       class="mt-3 inline-block text-sm text-indigo-600 hover:underline">
                        Create your first project →
                    </a>
                </div>
            @else
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach ($projects as $project)
                        @php
                            $total  = max(1, $project->total_tickets_count);
                            $closed = $total - $project->open_tickets_count;
                            $pct    = round($closed / $total * 100);
                        @endphp
                        <a href="{{ route('projects.show', $project) }}" wire:navigate
                           class="block bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md hover:border-indigo-300 transition group">
                            <div class="flex items-start gap-3 mb-3">
                                @if ($project->color)
                                    <div class="w-3.5 h-3.5 rounded-full flex-shrink-0 mt-1"
                                         style="background-color: {{ $project->color }}"></div>
                                @endif
                                <h3 class="font-semibold text-gray-900 group-hover:text-indigo-700 line-clamp-2 leading-snug">
                                    {{ $project->name }}
                                </h3>
                            </div>

                            {{-- Progress bar --}}
                            @if ($project->total_tickets_count > 0)
                                <div class="mb-3">
                                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                                        <span>Progress</span>
                                        <span>{{ $pct }}%</span>
                                    </div>
                                    <div class="h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-indigo-500 rounded-full transition-all"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                </div>
                            @endif

                            <div class="flex gap-4 text-sm text-gray-600">
                                <span>
                                    <span class="font-semibold text-gray-900">{{ $project->total_tickets_count }}</span>
                                    total
                                </span>
                                <span>
                                    <span class="font-semibold text-blue-600">{{ $project->open_tickets_count }}</span>
                                    open
                                </span>
                                @if ($project->overdue_tickets_count > 0)
                                    <span>
                                        <span class="font-semibold text-red-600">{{ $project->overdue_tickets_count }}</span>
                                        overdue
                                    </span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>

    </div>
</div>
