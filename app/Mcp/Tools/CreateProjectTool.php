<?php

namespace App\Mcp\Tools;

use App\Actions\Projects\CreateProject;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('create_project')]
#[Description('Create a new project owned by the authenticated user.')]
class CreateProjectTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();

        try {
            $validated = $request->validate([
                'name'        => ['required', 'string', 'min:3', 'max:255'],
                'description' => ['nullable', 'string'],
                'color'       => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            ]);

            $project = app(CreateProject::class)->handle(
                $user,
                $validated['name'],
                $validated['description'] ?? null,
                $validated['color'] ?? null,
            );

            return Response::json($project->only(['id', 'name', 'description', 'color', 'created_at']));
        } catch (ValidationException $e) {
            return Response::error('Validation error: ' . implode(', ', Arr::flatten($e->errors())));
        }
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name'        => $schema->string()->min(3)->max(255)->required(),
            'description' => $schema->string()->nullable(),
            'color'       => $schema->string()->nullable(),
        ];
    }
}

