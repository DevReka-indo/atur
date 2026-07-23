<?php

namespace App\Http\Controllers;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectTemplates\ProjectTemplatePreviewService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $workspaces = $this->availableWorkspaces($request->user());
        $restoredTemplate = $this->restoredTemplate($request);

        return view('project-template-gallery.index', compact(
            'templates',
            'summaries',
            'categories',
            'search',
            'categoryId',
            'workspaces',
            'restoredTemplate',
        ));
    }

    public function show(ProjectTemplate $projectTemplate): View
    {
        $projectTemplate->load('category');
        abort_unless($projectTemplate->isEffectivelyActive(), 404);

        $preview = $this->previewService->build($projectTemplate);
        $workspaces = $this->availableWorkspaces(request()->user());
        $restoredTemplate = $this->restoredTemplate(request());

        return view('project-template-gallery.show', compact(
            'projectTemplate',
            'preview',
            'workspaces',
            'restoredTemplate',
        ));
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function availableWorkspaces(User $user): Collection
    {
        return $user->workspaces()
            ->orderBy('workspaces.name')
            ->get(['workspaces.id', 'workspaces.name', 'workspaces.created_by'])
            ->filter(fn (Workspace $workspace): bool => $user->isSuperAdmin()
                || $workspace->isOwner($user)
                || in_array($workspace->pivot->role, [Workspace::ROLE_OWNER, Workspace::ROLE_ADMIN], true))
            ->values();
    }

    /**
     * @return array{id: int, name: string, category: string, summary: array<string, int|float>}|null
     */
    private function restoredTemplate(Request $request): ?array
    {
        $templateId = $request->old('project_template_id');
        if (! is_numeric($templateId)) {
            return null;
        }

        $template = ProjectTemplate::query()
            ->whereKey((int) $templateId)
            ->where('is_active', true)
            ->whereHas('category', fn ($query) => $query->where('is_active', true))
            ->with('category:id,name')
            ->first();

        if ($template === null) {
            return null;
        }

        $preview = $this->previewService->build($template);

        return [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->category->name,
            'summary' => $preview['summary'],
        ];
    }
}
