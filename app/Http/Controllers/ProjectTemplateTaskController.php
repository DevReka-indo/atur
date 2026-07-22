<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderProjectTemplateTasksRequest;
use App\Http\Requests\StoreProjectTemplateTaskRequest;
use App\Http\Requests\UpdateProjectTemplateDependencyRequest;
use App\Http\Requests\UpdateProjectTemplateTaskRequest;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use App\Models\ProjectTemplateTaskDependency;
use App\Services\ProjectTemplateHierarchyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProjectTemplateTaskController extends Controller
{
    public function __construct(private ProjectTemplateHierarchyService $hierarchyService) {}

    public function store(
        StoreProjectTemplateTaskRequest $request,
        ProjectTemplate $projectTemplate
    ): RedirectResponse {
        Gate::authorize('project-templates.update');
        $validated = $request->validated();

        DB::transaction(function () use ($projectTemplate, $validated): void {
            $template = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            $tasks = $this->hierarchyService->loadGraph($template);
            $parent = isset($validated['parent_id'])
                ? $tasks->firstWhere('id', (int) $validated['parent_id'])
                : null;

            if ($parent !== null) {
                $parent->loadMissing(['dependency', 'dependentDependencies']);
                $this->hierarchyService->assertParentCandidate($template, null, $parent, $tasks);
                $parent->update(['weight' => null]);
            }

            $position = array_key_exists('position', $validated)
                ? (int) $validated['position']
                : $template->tasks()->where('parent_id', $parent?->id)->count();
            $template->tasks()->create([
                'parent_id' => $parent?->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'],
                'weight' => $validated['weight'],
                'position' => $position,
                'start_offset_days' => $validated['start_offset_days'],
                'duration_days' => $validated['duration_days'],
            ]);

            $this->hierarchyService->normalizeSiblingPositions($template, $parent?->id);
            $this->hierarchyService->markStructureChanged($template, Auth::id());
        });

        return back()->with('success', 'Task template berhasil ditambahkan. Template dinonaktifkan untuk validasi ulang.');
    }

    public function update(
        UpdateProjectTemplateTaskRequest $request,
        ProjectTemplate $projectTemplate,
        ProjectTemplateTask $templateTask
    ): RedirectResponse {
        Gate::authorize('project-templates.update');
        $this->ensureTaskBelongsToTemplate($projectTemplate, $templateTask);
        $validated = $request->validated();

        DB::transaction(function () use ($projectTemplate, $templateTask, $validated): void {
            $template = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            $task = ProjectTemplateTask::query()->lockForUpdate()->findOrFail($templateTask->id);
            $tasks = $this->hierarchyService->loadGraph($template);
            $oldParentId = $task->parent_id;
            $newParentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;

            if ($newParentId !== null) {
                $parent = $tasks->firstWhere('id', $newParentId);
                if ($parent === null) {
                    throw ValidationException::withMessages(['parent_id' => 'Parent task tidak valid.']);
                }
                $parent->loadMissing(['dependency', 'dependentDependencies']);
                $this->hierarchyService->assertParentCandidate($template, $task, $parent, $tasks);
                $parent->update(['weight' => null]);
            }

            $hasChildren = $task->children()->exists();
            $task->update([
                'parent_id' => $newParentId,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'priority' => $validated['priority'],
                'weight' => $hasChildren ? null : $validated['weight'],
                'position' => $validated['position'] ?? $task->position,
                'start_offset_days' => $validated['start_offset_days'],
                'duration_days' => $validated['duration_days'],
            ]);

            $this->hierarchyService->normalizeSiblingPositions($template, $oldParentId);
            if ($oldParentId !== $newParentId) {
                $this->hierarchyService->normalizeSiblingPositions($template, $newParentId);
            }
            $this->hierarchyService->markStructureChanged($template, Auth::id());
        });

        return back()->with('success', 'Task template berhasil diperbarui. Template dinonaktifkan untuk validasi ulang.');
    }

    public function destroy(ProjectTemplate $projectTemplate, ProjectTemplateTask $templateTask): RedirectResponse
    {
        Gate::authorize('project-templates.update');
        $this->ensureTaskBelongsToTemplate($projectTemplate, $templateTask);

        DB::transaction(function () use ($projectTemplate, $templateTask): void {
            $template = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            $task = ProjectTemplateTask::query()->lockForUpdate()->findOrFail($templateTask->id);
            $parentId = $task->parent_id;
            $task->delete();
            $this->hierarchyService->normalizeSiblingPositions($template, $parentId);
            $this->hierarchyService->markStructureChanged($template, Auth::id());
        });

        return back()->with('success', 'Task template dan seluruh turunannya berhasil dihapus.');
    }

    public function reorder(
        ReorderProjectTemplateTasksRequest $request,
        ProjectTemplate $projectTemplate
    ): RedirectResponse {
        Gate::authorize('project-templates.update');
        $validated = $request->validated();

        DB::transaction(function () use ($projectTemplate, $validated): void {
            $template = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            $parentId = isset($validated['parent_id']) ? (int) $validated['parent_id'] : null;
            $siblings = $template->tasks()->where('parent_id', $parentId)->lockForUpdate()->get();
            $requestedIds = collect($validated['task_ids'])->map(fn ($id): int => (int) $id)->values();

            if ($siblings->pluck('id')->sort()->values()->all() !== $requestedIds->sort()->values()->all()) {
                throw ValidationException::withMessages([
                    'task_ids' => 'Daftar reorder harus berisi seluruh sibling dari parent yang sama.',
                ]);
            }

            foreach ($validated['task_ids'] as $position => $taskId) {
                ProjectTemplateTask::query()->whereKey($taskId)->update(['position' => $position]);
            }

            $this->hierarchyService->markStructureChanged($template, Auth::id());
        });

        return back()->with('success', 'Urutan task template berhasil diperbarui.');
    }

    public function updateDependency(
        UpdateProjectTemplateDependencyRequest $request,
        ProjectTemplate $projectTemplate,
        ProjectTemplateTask $templateTask
    ): RedirectResponse {
        Gate::authorize('project-templates.update');
        $this->ensureTaskBelongsToTemplate($projectTemplate, $templateTask);
        $validated = $request->validated();

        DB::transaction(function () use ($projectTemplate, $templateTask, $validated): void {
            $template = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            $task = ProjectTemplateTask::query()->lockForUpdate()->findOrFail($templateTask->id);
            $predecessor = ProjectTemplateTask::query()->lockForUpdate()->findOrFail($validated['predecessor_template_task_id']);

            if ($task->children()->exists() || $predecessor->children()->exists()
                || (int) $predecessor->project_template_id !== (int) $template->id) {
                throw ValidationException::withMessages([
                    'predecessor_template_task_id' => 'Dependency hanya diperbolehkan antar-leaf task dari template yang sama.',
                ]);
            }

            ProjectTemplateTaskDependency::query()->updateOrCreate(
                ['project_template_task_id' => $task->id],
                [
                    'project_template_id' => $template->id,
                    'predecessor_template_task_id' => $predecessor->id,
                    'dependency_type' => $validated['dependency_type'],
                    'lag_days' => $validated['lag_days'],
                ]
            );

            $tasks = $this->hierarchyService->loadGraph($template);
            $this->hierarchyService->validateGraph($template, $tasks);
            $this->hierarchyService->markStructureChanged($template, Auth::id());
        });

        return back()->with('success', 'Dependency task template berhasil diperbarui.');
    }

    public function destroyDependency(
        ProjectTemplate $projectTemplate,
        ProjectTemplateTask $templateTask
    ): RedirectResponse {
        Gate::authorize('project-templates.update');
        $this->ensureTaskBelongsToTemplate($projectTemplate, $templateTask);

        DB::transaction(function () use ($projectTemplate, $templateTask): void {
            $template = ProjectTemplate::query()->lockForUpdate()->findOrFail($projectTemplate->id);
            $dependency = ProjectTemplateTaskDependency::query()
                ->where('project_template_task_id', $templateTask->id)
                ->lockForUpdate()
                ->first();

            if ($dependency !== null) {
                $dependency->delete();
                $this->hierarchyService->markStructureChanged($template, Auth::id());
            }
        });

        return back()->with('success', 'Dependency task template berhasil dihapus.');
    }

    private function ensureTaskBelongsToTemplate(ProjectTemplate $template, ProjectTemplateTask $task): void
    {
        abort_unless((int) $task->project_template_id === (int) $template->id, 404);
    }
}
