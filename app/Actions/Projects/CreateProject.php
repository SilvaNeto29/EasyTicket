<?php

namespace App\Actions\Projects;

use App\Models\Project;
use App\Models\User;

class CreateProject
{
    public function handle(User $user, string $name, ?string $description, ?string $color): Project
    {
        return $user->projects()->create([
            'name'        => $name,
            'description' => $description,
            'color'       => $color,
        ]);
    }
}
