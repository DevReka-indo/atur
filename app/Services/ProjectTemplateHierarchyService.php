<?php

namespace App\Services;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateTask;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ProjectTemplateHierarchyService
{
    public const MAXIMUM_DEPTH = 2;

    /**
     * @return Collection<int, ProjectTemplateTask>
     */
    public function loadGraph(ProjectTemplate $template): Collection
    {
        return $template->tasks()
            ->with(['dependency.predecessor', 'children'])
            ->get();
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     */
    public function validateGraph(ProjectTemplate $template, Collection $tasks): void
    {
        if ($tasks->isEmpty()) {
            $this->fail('tasks', 'Template harus memiliki minimal satu leaf task.');
        }

        $tasksById = $tasks->keyBy('id');
        $parentIds = $tasks->pluck('parent_id')->filter()->map(fn ($id): int => (int) $id)->unique();
        $leafTasks = $tasks->reject(fn (ProjectTemplateTask $task): bool => $parentIds->contains($task->id));

        if ($leafTasks->isEmpty()) {
            $this->fail('tasks', 'Template harus memiliki minimal satu leaf task.');
        }

        foreach ($tasks as $task) {
            if ((int) $task->project_template_id !== (int) $template->id) {
                $this->fail('tasks', 'Seluruh task harus berasal dari template yang sama.');
            }

            $depth = $this->depth($task, $tasksById);
            if ($depth > self::MAXIMUM_DEPTH) {
                $this->fail('parent_id', 'Hierarchy task maksimal terdiri dari tiga level.');
            }

            $isLeaf = ! $parentIds->contains($task->id);
            if ($isLeaf && ($task->weight === null || (float) $task->weight <= 0)) {
                $this->fail('weight', 'Leaf task wajib memiliki weight lebih besar dari 0.');
            }

            if (! $isLeaf && $task->weight !== null) {
                $this->fail('weight', 'Parent task tidak boleh menyimpan weight.');
            }

            $dependency = $task->dependency;
            if ($dependency === null) {
                continue;
            }

            $predecessor = $tasksById->get($dependency->predecessor_template_task_id);
            if (! $isLeaf || $predecessor === null || $parentIds->contains($predecessor->id)) {
                $this->fail('predecessor_template_task_id', 'Dependency hanya diperbolehkan antar-leaf task.');
            }

            if ((int) $dependency->project_template_id !== (int) $template->id
                || (int) $predecessor->project_template_id !== (int) $template->id) {
                $this->fail('predecessor_template_task_id', 'Dependency harus berada dalam template yang sama.');
            }

            if ((int) $task->id === (int) $predecessor->id) {
                $this->fail('predecessor_template_task_id', 'Task tidak dapat menjadi predecessor untuk dirinya sendiri.');
            }
        }

        $this->assertDependencyGraphHasNoCycle($tasks);
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     * @return array<int, float>
     */
    public function aggregateWeights(Collection $tasks): array
    {
        $childrenByParent = $tasks->groupBy(fn (ProjectTemplateTask $task): int => (int) ($task->parent_id ?? 0));
        $weights = [];
        $visiting = [];

        $resolve = function (ProjectTemplateTask $task) use (&$resolve, &$weights, &$visiting, $childrenByParent): float {
            if (array_key_exists($task->id, $weights)) {
                return $weights[$task->id];
            }

            if (isset($visiting[$task->id])) {
                $this->fail('parent_id', 'Hierarchy template membentuk circular relationship.');
            }

            $visiting[$task->id] = true;
            $children = $childrenByParent->get($task->id, collect());
            $weight = $children->isEmpty()
                ? (float) $task->weight
                : (float) $children->sum(fn (ProjectTemplateTask $child): float => $resolve($child));
            unset($visiting[$task->id]);

            return $weights[$task->id] = round($weight, 2);
        };

        foreach ($tasks as $task) {
            $resolve($task);
        }

        return $weights;
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     */
    public function totalLeafWeight(Collection $tasks): float
    {
        $parentIds = $tasks->pluck('parent_id')->filter()->map(fn ($id): int => (int) $id)->unique();

        return round((float) $tasks
            ->reject(fn (ProjectTemplateTask $task): bool => $parentIds->contains($task->id))
            ->sum('weight'), 2);
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     */
    public function depth(ProjectTemplateTask $task, Collection $tasks): int
    {
        $tasksById = $tasks->keyBy('id');
        $depth = 0;
        $parentId = $task->parent_id;
        $visited = [$task->id => true];

        while ($parentId !== null) {
            if (isset($visited[$parentId])) {
                $this->fail('parent_id', 'Hierarchy template membentuk circular relationship.');
            }

            $visited[$parentId] = true;
            $parent = $tasksById->get($parentId);
            if ($parent === null) {
                $this->fail('parent_id', 'Parent task tidak ditemukan dalam template yang sama.');
            }

            $depth++;
            $parentId = $parent->parent_id;
        }

        return $depth;
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     */
    public function assertParentCandidate(
        ProjectTemplate $template,
        ?ProjectTemplateTask $task,
        ProjectTemplateTask $parent,
        Collection $tasks
    ): void {
        if ((int) $parent->project_template_id !== (int) $template->id) {
            $this->fail('parent_id', 'Parent task harus berasal dari template yang sama.');
        }

        if ($task !== null && $task->is($parent)) {
            $this->fail('parent_id', 'Task tidak dapat menjadi parent untuk dirinya sendiri.');
        }

        $candidate = $task?->replicate() ?? new ProjectTemplateTask;
        $candidate->id = $task?->id ?? PHP_INT_MAX;
        $candidate->parent_id = $parent->id;
        $graph = $tasks->reject(fn (ProjectTemplateTask $item): bool => $task !== null && $item->is($task))->push($candidate);

        foreach ($graph as $graphTask) {
            if ($this->depth($graphTask, $graph) > self::MAXIMUM_DEPTH) {
                $this->fail('parent_id', 'Hierarchy task maksimal terdiri dari tiga level.');
            }
        }

        if ($task !== null && $this->isDescendant($parent, $task, $tasks)) {
            $this->fail('parent_id', 'Descendant task tidak dapat dipilih sebagai parent.');
        }

        if ($parent->dependency !== null || $parent->dependentDependencies->isNotEmpty()) {
            $this->fail('parent_id', 'Task yang terlibat dependency tidak dapat dijadikan parent.');
        }
    }

    public function markStructureChanged(ProjectTemplate $template, int $actorId): ProjectTemplate
    {
        $lockedTemplate = ProjectTemplate::query()->lockForUpdate()->findOrFail($template->id);
        $lockedTemplate->update([
            'version' => $lockedTemplate->version + 1,
            'is_active' => false,
            'updated_by' => $actorId,
        ]);

        return $lockedTemplate;
    }

    public function normalizeSiblingPositions(ProjectTemplate $template, ?int $parentId): void
    {
        $siblings = $template->tasks()
            ->where('parent_id', $parentId)
            ->orderBy('position')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        foreach ($siblings as $position => $sibling) {
            if ($sibling->position !== $position) {
                $sibling->update(['position' => $position]);
            }
        }
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     */
    private function assertDependencyGraphHasNoCycle(Collection $tasks): void
    {
        $predecessors = $tasks
            ->filter(fn (ProjectTemplateTask $task): bool => $task->dependency !== null)
            ->mapWithKeys(fn (ProjectTemplateTask $task): array => [
                $task->id => (int) $task->dependency->predecessor_template_task_id,
            ]);

        foreach ($tasks as $task) {
            $visited = [];
            $currentId = $task->id;

            while ($predecessors->has($currentId)) {
                if (isset($visited[$currentId])) {
                    $this->fail('predecessor_template_task_id', 'Dependency template membentuk circular relationship.');
                }

                $visited[$currentId] = true;
                $currentId = $predecessors->get($currentId);
            }
        }
    }

    /**
     * @param  Collection<int, ProjectTemplateTask>  $tasks
     */
    private function isDescendant(ProjectTemplateTask $candidate, ProjectTemplateTask $task, Collection $tasks): bool
    {
        $tasksById = $tasks->keyBy('id');
        $parentId = $candidate->parent_id;
        $visited = [];

        while ($parentId !== null) {
            if (isset($visited[$parentId])) {
                $this->fail('parent_id', 'Hierarchy template membentuk circular relationship.');
            }

            if ((int) $parentId === (int) $task->id) {
                return true;
            }

            $visited[$parentId] = true;
            $parentId = $tasksById->get($parentId)?->parent_id;
        }

        return false;
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }
}
