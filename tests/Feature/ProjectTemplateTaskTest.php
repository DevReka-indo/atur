<?php

namespace Tests\Feature;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Models\ProjectTemplateTask;
use App\Models\ProjectTemplateTaskDependency;
use App\Models\User;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectTemplateTaskTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    private User $editor;

    private ProjectTemplate $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createProjectTemplateTestSchema();
        $this->editor = User::factory()->contributor()->create();
        $category = ProjectTemplateCategory::factory()->for($this->editor, 'creator')->create();
        $this->template = ProjectTemplate::factory()->for($category, 'category')->for($this->editor, 'creator')->create();
    }

    public function test_root_child_and_grandchild_can_be_created_and_level_four_is_rejected(): void
    {
        $root = $this->storeTask(null, 'Root', 100);
        $child = $this->storeTask($root, 'Child', 100);
        $grandchild = $this->storeTask($child, 'Grandchild', 100);

        $this->assertNull($root->fresh()->weight);
        $this->assertNull($child->fresh()->weight);
        $this->assertSame('100.00', $grandchild->fresh()->weight);

        $this->actingAs($this->editor)
            ->post(route('project-templates.tasks.store', $this->template), $this->taskPayload([
                'parent_id' => $grandchild->id,
                'name' => 'Level four',
            ]))
            ->assertSessionHasErrors('parent_id');
        $this->assertDatabaseMissing('project_template_tasks', ['name' => 'Level four']);
    }

    public function test_cross_template_parent_and_hierarchy_cycle_are_rejected(): void
    {
        $other = ProjectTemplate::factory()->for($this->template->category, 'category')->for($this->editor, 'creator')->create();
        $otherParent = ProjectTemplateTask::factory()->for($other, 'template')->create();

        $this->actingAs($this->editor)
            ->post(route('project-templates.tasks.store', $this->template), $this->taskPayload([
                'parent_id' => $otherParent->id,
            ]))
            ->assertSessionHasErrors('parent_id');

        $root = $this->storeTask(null, 'Cycle root', 100);
        $child = $this->storeTask($root, 'Cycle child', 100);

        $this->actingAs($this->editor)
            ->put(route('project-templates.tasks.update', [$this->template, $root]), $this->taskPayload([
                'parent_id' => $root->id,
                'name' => $root->name,
                'weight' => null,
            ]))
            ->assertSessionHasErrors('parent_id');

        $this->actingAs($this->editor)
            ->put(route('project-templates.tasks.update', [$this->template, $root]), $this->taskPayload([
                'parent_id' => $child->id,
                'name' => $root->name,
                'weight' => null,
            ]))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_structural_change_increments_version_once_and_deactivates_template(): void
    {
        $this->template->update(['version' => 4, 'is_active' => true]);

        $this->storeTask(null, 'Structural task', 100);

        $this->assertSame(5, $this->template->fresh()->version);
        $this->assertFalse($this->template->fresh()->is_active);
    }

    public function test_adding_a_task_does_not_require_existing_relative_weights_to_be_adjusted(): void
    {
        $first = $this->storeTask(null, 'Relative first', 25);
        $second = $this->storeTask(null, 'Relative second', 50);

        $this->assertSame('25.00', $first->fresh()->weight);
        $this->assertSame('50.00', $second->fresh()->weight);
        $this->assertSame(75.0, $this->template->totalLeafWeight());
    }

    public function test_reorder_requires_all_siblings_and_updates_positions_once(): void
    {
        $first = $this->storeTask(null, 'First', 50);
        $second = $this->storeTask(null, 'Second', 50);
        $version = $this->template->fresh()->version;

        $this->actingAs($this->editor)
            ->patch(route('project-templates.tasks.reorder', $this->template), [
                'parent_id' => null,
                'task_ids' => [$second->id, $first->id],
            ])->assertRedirect();

        $this->assertSame(0, $second->fresh()->position);
        $this->assertSame(1, $first->fresh()->position);
        $this->assertSame($version + 1, $this->template->fresh()->version);

        $this->actingAs($this->editor)
            ->patch(route('project-templates.tasks.reorder', $this->template), [
                'task_ids' => [$first->id],
            ])->assertSessionHasErrors('task_ids');
    }

    #[DataProvider('dependencyTypeProvider')]
    public function test_leaf_dependencies_support_all_types_and_lag(string $type): void
    {
        $predecessor = $this->storeTask(null, 'Predecessor '.$type, 50);
        $successor = $this->storeTask(null, 'Successor '.$type, 50);

        $this->actingAs($this->editor)
            ->put(route('project-templates.tasks.dependency.update', [$this->template, $successor]), [
                'predecessor_template_task_id' => $predecessor->id,
                'dependency_type' => $type,
                'lag_days' => 2,
            ])->assertRedirect();

        $this->assertDatabaseHas('project_template_task_dependencies', [
            'project_template_task_id' => $successor->id,
            'predecessor_template_task_id' => $predecessor->id,
            'dependency_type' => $type,
            'lag_days' => 2,
        ]);
    }

    public static function dependencyTypeProvider(): array
    {
        return [['FS'], ['SS'], ['FF'], ['SF']];
    }

    public function test_invalid_self_cross_template_summary_and_cycle_dependencies_are_rejected(): void
    {
        $first = $this->storeTask(null, 'First dependency', 50);
        $second = $this->storeTask(null, 'Second dependency', 50);

        $this->actingAs($this->editor)->put(
            route('project-templates.tasks.dependency.update', [$this->template, $first]),
            ['predecessor_template_task_id' => $first->id, 'dependency_type' => 'FS', 'lag_days' => 0],
        )->assertSessionHasErrors('predecessor_template_task_id');

        $this->actingAs($this->editor)->put(
            route('project-templates.tasks.dependency.update', [$this->template, $second]),
            ['predecessor_template_task_id' => $first->id, 'dependency_type' => 'FS', 'lag_days' => 0],
        )->assertRedirect();
        $this->actingAs($this->editor)->put(
            route('project-templates.tasks.dependency.update', [$this->template, $first]),
            ['predecessor_template_task_id' => $second->id, 'dependency_type' => 'FS', 'lag_days' => 0],
        )->assertSessionHasErrors('predecessor_template_task_id');
        $this->assertNull($first->fresh()->dependency);

        $parent = $this->storeTask(null, 'Summary', 20);
        $this->storeTask($parent, 'Summary child', 20);
        $this->actingAs($this->editor)->put(
            route('project-templates.tasks.dependency.update', [$this->template, $second]),
            ['predecessor_template_task_id' => $parent->id, 'dependency_type' => 'FS', 'lag_days' => 0],
        )->assertSessionHasErrors('predecessor_template_task_id');

        $other = ProjectTemplate::factory()->for($this->template->category, 'category')->for($this->editor, 'creator')->create();
        $otherTask = ProjectTemplateTask::factory()->for($other, 'template')->create();
        $this->actingAs($this->editor)->put(
            route('project-templates.tasks.dependency.update', [$this->template, $second]),
            ['predecessor_template_task_id' => $otherTask->id, 'dependency_type' => 'FS', 'lag_days' => 0],
        )->assertSessionHasErrors('predecessor_template_task_id');
    }

    public function test_negative_lag_invalid_type_and_adding_child_to_dependency_task_are_rejected(): void
    {
        $first = $this->storeTask(null, 'Dependency first', 50);
        $second = $this->storeTask(null, 'Dependency second', 50);

        foreach ([
            ['dependency_type' => 'XX', 'lag_days' => 0],
            ['dependency_type' => 'FS', 'lag_days' => -1],
        ] as $invalid) {
            $this->actingAs($this->editor)->put(
                route('project-templates.tasks.dependency.update', [$this->template, $second]),
                ['predecessor_template_task_id' => $first->id] + $invalid,
            )->assertSessionHasErrors();
        }

        ProjectTemplateTaskDependency::create([
            'project_template_id' => $this->template->id,
            'project_template_task_id' => $second->id,
            'predecessor_template_task_id' => $first->id,
            'dependency_type' => 'FS',
            'lag_days' => 0,
        ]);

        $this->actingAs($this->editor)
            ->post(route('project-templates.tasks.store', $this->template), $this->taskPayload([
                'parent_id' => $first->id,
                'name' => 'Blocked child',
            ]))->assertSessionHasErrors('parent_id');
    }

    public function test_delete_removes_subtree_and_increments_version_once(): void
    {
        $root = $this->storeTask(null, 'Delete root', 100);
        $child = $this->storeTask($root, 'Delete child', 100);
        $version = $this->template->fresh()->version;

        $this->actingAs($this->editor)
            ->delete(route('project-templates.tasks.destroy', [$this->template, $root]))
            ->assertRedirect();

        $this->assertDatabaseMissing('project_template_tasks', ['id' => $root->id]);
        $this->assertDatabaseMissing('project_template_tasks', ['id' => $child->id]);
        $this->assertSame($version + 1, $this->template->fresh()->version);
    }

    private function storeTask(?ProjectTemplateTask $parent, string $name, float $weight): ProjectTemplateTask
    {
        $this->actingAs($this->editor)
            ->post(route('project-templates.tasks.store', $this->template), $this->taskPayload([
                'parent_id' => $parent?->id,
                'name' => $name,
                'weight' => $weight,
            ]))->assertRedirect();

        return ProjectTemplateTask::query()->where('name', $name)->firstOrFail();
    }

    private function taskPayload(array $overrides = []): array
    {
        return array_merge([
            'parent_id' => null,
            'name' => 'Template task',
            'description' => null,
            'priority' => 'medium',
            'weight' => 10,
            'position' => 0,
            'start_offset_days' => 0,
            'duration_days' => 1,
        ], $overrides);
    }
}
