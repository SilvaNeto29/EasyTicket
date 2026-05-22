<?php

namespace App\Mcp\Tools;

use App\Actions\Projects\UpdateProject;
use App\Models\Project;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('update_project')]
#[Description('Update one or more fields of an existing project. Fields not provided remain unchanged.')]
class UpdateProjectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'project_id'  => ['required', 'integer'],
                'name'        => ['sometimes', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string'],
                'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]);

            $project = Project::where('id', $validated['project_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $all = $request->all();
            $project = app(UpdateProject::class)->handle(
                $project,
                array_key_exists('name', $all) ? $all['name'] : $project->name,
                array_key_exists('description', $all) ? $all['description'] : $project->description,
                array_key_exists('color', $all) ? $all['color'] : $project->color,
            );

            return Response::json($project->toArray());
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id'  => $schema->integer()->required(),
            'name'        => $schema->string()->min(3)->max(255)->nullable(),
            'description' => $schema->string()->nullable(),
            'color'       => $schema->string()->nullable(),
        ];
    }
}
