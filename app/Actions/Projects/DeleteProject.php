<?php

namespace App\Actions\Projects;

use App\Models\Project;

class DeleteProject
{
    public function handle(Project $project): void
    {
        $project->delete();
    }
}
