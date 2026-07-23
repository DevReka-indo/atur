<?php

namespace App\Http\Controllers;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Services\ProjectTemplates\ProjectTemplatePreviewService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProjectTemplateGalleryController extends Controller
{
    public function __construct(
        private ProjectTemplatePreviewService $previewService,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $categoryId = $request->integer('category');

        $templates = ProjectTemplate::query()
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with('category:id,name')
            ->when($search !== '', fn ($query) => $query->where(function ($searchQuery) use ($search): void {
                $searchQuery
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('category', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
            }))
            ->when($categoryId > 0, fn ($query) => $query->where('project_template_category_id', $categoryId))
            ->orderBy(
                ProjectTemplateCategory::query()
                    ->select('name')
                    ->whereColumn('project_template_categories.id', 'project_templates.project_template_category_id')
                    ->limit(1)
            )
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $summaries = $this->previewService->summaries($templates->getCollection());
        $categories = ProjectTemplateCategory::query()
            ->where('is_active', true)
            ->whereHas('templates', fn ($query) => $query->where('is_active', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('project-template-gallery.index', compact(
            'templates',
            'summaries',
            'categories',
            'search',
            'categoryId',
        ));
    }

    public function show(ProjectTemplate $projectTemplate): View
    {
        $projectTemplate->load('category');
        abort_unless($projectTemplate->isEffectivelyActive(), 404);

        $preview = $this->previewService->build($projectTemplate);

        return view('project-template-gallery.show', compact('projectTemplate', 'preview'));
    }
}
