<?php

namespace App\Actions\Projects;

use App\Models\Project;

class UpdateProject
{
    public function handle(Project $project, string $name, ?string $description, ?string $color): Project
    {
        $project->update([
            'name'        => $name,
            'description' => $description,
            'color'       => $color,
        ]);

        return $project->fresh();
    }
}
