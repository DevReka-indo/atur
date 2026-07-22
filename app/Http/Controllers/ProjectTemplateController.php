<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectTemplateRequest;
use App\Http\Requests\UpdateProjectTemplateRequest;
use App\Http\Requests\UpdateProjectTemplateStatusRequest;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Models\ProjectTemplateTask;
use App\Services\ProjectTemplateHierarchyService;
use App\Services\ProjectTemplateScheduleCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProjectTemplateController extends Controller
{
    public function __construct(
        private ProjectTemplateHierarchyService $hierarchyService,
        private ProjectTemplateScheduleCalculator $scheduleCalculator,
    ) {}

    public function index(Request $request): View
    {
        Gate::authorize('project-templates.view');
        $search = $request->string('search')->trim()->toString();
        $categoryId = $request->integer('category_id');
        $status = $request->string('status')->toString();

        $templates = ProjectTemplate::query()
            ->with(['category:id,name,slug,is_active', 'creator:id,name', 'updater:id,name', 'tasks'])
            ->withCount('tasks')
            ->when($search !== '', fn ($query) => $query->where(
                fn ($nested) => $nested->where('name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")
            ))
            ->when($categoryId > 0, fn ($query) => $query->where('project_template_category_id', $categoryId))
            ->when(in_array($status, ['active', 'inactive'], true), fn ($query) => $query->where('is_active', $status === 'active'))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $categories = ProjectTemplateCategory::query()->orderBy('name')->get(['id', 'name']);

        return view('project-templates.index', compact('templates', 'categories', 'search', 'categoryId', 'status'));
    }

    public function create(): View
    {
        Gate::authorize('project-templates.create');
        $categories = ProjectTemplateCategory::query()->where('is_active', true)->orderBy('name')->get();

        return view('project-templates.create', compact('categories'));
    }

    public function store(StoreProjectTemplateRequest $request): RedirectResponse
    {
        Gate::authorize('project-templates.create');
        $validated = $request->validated();

        $template = DB::transaction(fn (): ProjectTemplate => ProjectTemplate::query()->create([
            'project_template_category_id' => $validated['project_template_category_id'],
            'name' => $validated['name'],
            'slug' => $this->uniqueSlug($validated['name']),
            'description' => $validated['description'] ?? null,
            'version' => 1,
            'is_active' => false,
            'created_by' => Auth::id(),
            'updated_by' => Auth::id(),
        ]));

        return redirect()->route('project-templates.show', $template)
            ->with('success', 'Template berhasil dibuat. Tambahkan task sebelum mengaktifkannya.');
    }

    public function show(ProjectTemplate $projectTemplate): View
    {
        Gate::authorize('project-templates.view');

        return $this->editorView($projectTemplate, false);
    }

    public function edit(ProjectTemplate $projectTemplate): View
    {
        Gate::authorize('project-templates.update');

        return $this->editorView($projectTemplate, true);
    }

    public function update(UpdateProjectTemplateRequest $request, ProjectTemplate $projectTemplate): RedirectResponse
    {
        Gate::authorize('project-templates.update');
        $validated = $request->validated();

        DB::transaction(function () use ($projectTemplate, $validated): void {
            $projectTemplate->update([
                'project_template_category_id' => $validated['project_template_category_id'],
                'name' => $validated['name'],
                'slug' => $this->uniqueSlug($validated['name'], $projectTemplate),
                'description' => $validated['description'] ?? null,
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()->route('project-templates.show', $projectTemplate->fresh())
            ->with('success', 'Metadata template berhasil diperbarui.');
    }

    public function toggleStatus(
        UpdateProjectTemplateStatusRequest $request,
        ProjectTemplate $projectTemplate
    ): RedirectResponse {
        Gate::authorize('project-templates.update');
        $activate = (bool) $request->validated('is_active');

        DB::transaction(function () use ($projectTemplate, $activate): void {
            $lockedTemplate = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            if ($activate) {
                $lockedTemplate->load('category');
                if (! $lockedTemplate->category?->isEffectivelyActive()) {
                    throw ValidationException::withMessages(['is_active' => 'Kategori template harus aktif sebelum template diaktifkan.']);
                }

                $tasks = $this->hierarchyService->loadGraph($lockedTemplate);
                $this->hierarchyService->validateGraph($lockedTemplate, $tasks);
                $this->scheduleCalculator->calculate(CarbonImmutable::parse('2000-01-01'), $tasks);
            }

            $lockedTemplate->update(['is_active' => $activate, 'updated_by' => Auth::id()]);
        });

        return back()->with('success', $activate ? 'Template berhasil diaktifkan.' : 'Template berhasil dinonaktifkan.');
    }

    public function destroy(ProjectTemplate $projectTemplate): RedirectResponse
    {
        Gate::authorize('project-templates.delete');

        DB::transaction(fn () => $projectTemplate->delete());

        return redirect()->route('project-templates.index')->with('success', 'Template berhasil dihapus.');
    }

    private function editorView(ProjectTemplate $template, bool $editing): View
    {
        $template->load(['category', 'creator:id,name', 'updater:id,name']);
        $tasks = $this->hierarchyService->loadGraph($template);
        $tasksByParent = $tasks->groupBy(fn (ProjectTemplateTask $task): int => (int) ($task->parent_id ?? 0));
        $rootTasks = $tasksByParent->get(0, collect());
        $parentIds = $tasks->pluck('parent_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $leafTasks = $tasks->reject(fn (ProjectTemplateTask $task): bool => $parentIds->contains($task->id));
        $totalLeafWeight = $this->hierarchyService->totalLeafWeight($tasks);
        $warnings = collect();
        $schedule = [];

        try {
            $this->hierarchyService->validateGraph($template, $tasks);
            $schedule = $this->scheduleCalculator->calculate(CarbonImmutable::parse('2000-01-01'), $tasks);
        } catch (ValidationException $exception) {
            $warnings = collect($exception->errors())->flatten();
        }

        $categories = $editing
            ? ProjectTemplateCategory::query()->orderBy('name')->get()
            : collect();

        return view($editing ? 'project-templates.edit' : 'project-templates.show', compact(
            'template',
            'tasks',
            'tasksByParent',
            'rootTasks',
            'leafTasks',
            'totalLeafWeight',
            'warnings',
            'schedule',
            'categories',
        ));
    }

    private function uniqueSlug(string $name, ?ProjectTemplate $ignore = null): string
    {
        $baseSlug = Str::slug($name) ?: 'project-template';
        $slug = $baseSlug;
        $suffix = 2;

        while (ProjectTemplate::withTrashed()
            ->where('slug', $slug)
            ->when($ignore !== null, fn ($query) => $query->whereKeyNot($ignore->id))
            ->exists()) {
            $slug = $baseSlug.'-'.$suffix++;
        }

        return $slug;
    }
}
