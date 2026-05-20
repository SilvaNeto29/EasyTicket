<?php

namespace App\Http\Controllers;

use App\Actions\Export\ExportUserData;
use Illuminate\Http\JsonResponse;

class ExportController extends Controller
{
    public function download(ExportUserData $action): JsonResponse
    {
        $data = $action->handle(auth()->user());

        $filename = 'easyticket-export-' . now()->format('Y-m-d') . '.json';

        return response()->json($data)
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
}
