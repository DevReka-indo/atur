<?php

namespace App\Http\Controllers;

use App\Models\ProjectTemplate;
use App\Services\ProjectTemplates\ProjectTemplatePreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProjectTemplatePreviewController extends Controller
{
    public function __invoke(
        Request $request,
        ProjectTemplate $projectTemplate,
        ProjectTemplatePreviewService $previewService,
    ): JsonResponse {
        $validated = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'due_date' => [
                'nullable',
                'date_format:Y-m-d',
                Rule::when($request->filled('start_date'), ['after_or_equal:start_date']),
            ],
        ]);

        $projectTemplate->load('category');
        abort_unless($projectTemplate->isEffectivelyActive(), 404);

        return response()->json($previewService->build(
            $projectTemplate,
            $validated['start_date'] ?? null,
            $validated['due_date'] ?? null,
        ));
    }
}
