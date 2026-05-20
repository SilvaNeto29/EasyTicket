<?php

namespace App\Actions\Export;

use App\Models\User;

class ExportUserData
{
    public function handle(User $user): array
    {
        return $user->projects()
            ->with(['tickets' => fn ($q) => $q->ordered()])
            ->orderBy('created_at')
            ->get()
            ->map(fn ($project) => [
                'id'          => $project->id,
                'name'        => $project->name,
                'description' => $project->description,
                'color'       => $project->color,
                'created_at'  => $project->created_at->toISOString(),
                'updated_at'  => $project->updated_at->toISOString(),
                'tickets'     => $project->tickets->map(fn ($t) => [
                    'id'          => $t->id,
                    'title'       => $t->title,
                    'description' => $t->description,
                    'status'      => $t->status->value,
                    'priority'    => $t->priority->value,
                    'due_date'    => $t->due_date?->toDateString(),
                    'created_at'  => $t->created_at->toISOString(),
                    'updated_at'  => $t->updated_at->toISOString(),
                ])->values()->all(),
            ])->values()->all();
    }
}
