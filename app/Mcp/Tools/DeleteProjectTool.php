<?php

namespace App\Mcp\Tools;

use App\Actions\Projects\DeleteProject;
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

#[Name('delete_project')]
#[Description('Permanently delete a project and all its tickets. This action is irreversible.')]
class DeleteProjectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'project_id' => ['required', 'integer'],
            ]);

            $project = Project::where('id', $validated['project_id'])
                ->where('user_id', $user->id)
                ->firstOrFail();

            $projectId = $project->id;
            app(DeleteProject::class)->handle($project);

            return Response::json(['deleted' => true, 'project_id' => $projectId]);
        } catch (ModelNotFoundException) {
            return Response::error('Project not found.');
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'project_id' => $schema->integer()->required(),
        ];
    }
}
