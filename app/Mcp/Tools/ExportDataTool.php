<?php

namespace App\Mcp\Tools;

use App\Actions\Export\ExportUserData;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Tool;

#[Name('export_data')]
#[Description('Export all projects and tickets for the authenticated user.')]
class ExportDataTool extends Tool
{
    public function handle(Request $request): Response
    {
        $user = $request->user();
        $data = app(ExportUserData::class)->handle($user);

        return Response::json($data);
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
