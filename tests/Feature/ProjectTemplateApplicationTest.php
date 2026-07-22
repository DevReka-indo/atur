<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Models\ProjectTemplateTask;
use App\Models\ProjectTemplateTaskDependency;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectTemplateApplicationTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createProjectTemplateTestSchema();
        DB::connection()->getPdo()->sqliteCreateFunction('FIELD', function ($value, ...$values): int {
            $position = array_search($value, $values, true);

            return $position === false ? 0 : $position + 1;
        });
    }

    public function test_project_without_template_keeps_six_default_tasks(): void
    {
        [$user, $workspace] = $this->workspaceOwner();

        $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace))->assertRedirect();

        $project = Project::query()->firstOrFail();
        $this->assertNull($project->project_template_id);
        $this->assertCount(6, $project->tasks);
        $this->assertSame([
            'Project Kickoff',
            'Requirement Gathering',
            'Planning & Scheduling',
            'Execution',
            'Review & Testing',
            'Project Closing',
        ], $project->tasks()->pluck('name')->all());
    }

    public function test_active_template_replaces_defaults_and_copies_graph_schedule_and_lineage(): void
    {
        [$user, $workspace] = $this->workspaceOwner();
        [$template, $root, $firstLeaf, $secondLeaf] = $this->activeTemplate($user);

        $response = $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
            'due_date' => '2026-07-21',
        ]));
        $response->assertRedirect();

        $project = Project::query()->firstOrFail();
        $this->assertSame($template->id, $project->project_template_id);
        $this->assertSame($template->name, $project->source_template_name);
        $this->assertSame($template->version, $project->source_template_version);
        $this->assertSame('2026-07-30', $project->end_date->toDateString());
        $this->assertCount(3, $project->tasks);
        $this->assertDatabaseMissing('tasks', ['project_id' => $project->id, 'name' => 'Project Kickoff']);

        $runtimeRoot = Task::query()->where('project_id', $project->id)->where('name', $root->name)->firstOrFail();
        $runtimeFirst = Task::query()->where('project_id', $project->id)->where('name', $firstLeaf->name)->firstOrFail();
        $runtimeSecond = Task::query()->where('project_id', $project->id)->where('name', $secondLeaf->name)->firstOrFail();
        $this->assertSame($runtimeRoot->id, $runtimeFirst->parent_task_id);
        $this->assertSame($runtimeRoot->id, $runtimeSecond->parent_task_id);
        $this->assertSame($runtimeFirst->id, $runtimeSecond->predecessor_id);
        $this->assertSame('FS', $runtimeSecond->dependency_type);
        $this->assertSame(0, $runtimeRoot->position);
        $this->assertSame(0, $runtimeFirst->position);
        $this->assertSame(1, $runtimeSecond->position);
        $this->assertSame('2026-07-20', $runtimeFirst->start_date->toDateString());
        $this->assertSame('2026-07-22', $runtimeFirst->due_date->toDateString());
        $this->assertSame('2026-07-26', $runtimeSecond->start_date->toDateString());
        $this->assertSame('2026-07-30', $runtimeSecond->due_date->toDateString());
        $this->assertSame('125.00', $runtimeRoot->weight);
        $this->assertSame('40.00', $runtimeFirst->subtask_weight_percentage);
        $this->assertSame('60.00', $runtimeSecond->subtask_weight_percentage);
        $this->assertSame(100.0, (float) $runtimeFirst->subtask_weight_percentage + (float) $runtimeSecond->subtask_weight_percentage);

        foreach ($project->tasks as $task) {
            $this->assertSame('to_do', $task->status);
            $this->assertNull($task->assignee_id);
            $this->assertNull($task->completed_at);
            $this->assertNull($task->stopped_progress);
            $this->assertCount(0, $task->assignees);
        }

        $this->assertDatabaseCount('project_baselines', 1);
        $this->assertDatabaseCount('task_baselines', 3);
        $this->assertDatabaseHas('actual_progress', [
            'project_id' => $project->id,
            'total_tasks_count' => 2,
            'completed_tasks_count' => 0,
        ]);
    }

    public function test_user_without_template_management_permission_can_apply_an_active_template(): void
    {
        [$member, $workspace] = $this->workspaceOwner();
        [$template] = $this->activeTemplate($member);
        $this->assertFalse($member->can('project-templates.view'));

        $this->actingAs($member)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
        ]))->assertRedirect();

        $this->assertDatabaseHas('projects', ['project_template_id' => $template->id]);
    }

    public function test_inactive_template_or_category_is_rejected_and_transaction_rolls_back(): void
    {
        [$user, $workspace] = $this->workspaceOwner();
        [$template] = $this->activeTemplate($user);
        $template->update(['is_active' => false]);

        $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
        ]))->assertSessionHasErrors('project_template_id');

        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('project_baselines', 0);
        $this->assertDatabaseCount('planned_progress', 0);
        $this->assertDatabaseCount('actual_progress', 0);
        $this->assertDatabaseCount('activity_logs', 0);

        $template->update(['is_active' => true]);
        $template->category()->update(['is_active' => false]);
        $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
        ]))->assertSessionHasErrors('project_template_id');
        $this->assertDatabaseCount('projects', 0);

        $template->category()->update(['is_active' => true]);
        $template->tasks()->where('name', 'Release')->update(['weight' => null]);
        $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
        ]))->assertSessionHasErrors('weight');
        $this->assertDatabaseCount('projects', 0);
        $this->assertDatabaseCount('project_members', 0);
        $this->assertDatabaseCount('tasks', 0);
        $this->assertDatabaseCount('project_baselines', 0);
        $this->assertDatabaseCount('planned_progress', 0);
        $this->assertDatabaseCount('actual_progress', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    public function test_deleting_a_leaf_keeps_remaining_runtime_weights_normalized(): void
    {
        $user = User::factory()->contributor()->create();
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        [$template, $templateRoot, $firstLeaf] = $this->activeTemplate($user);

        $this->actingAs($user)
            ->delete(route('project-templates.tasks.destroy', [$template, $firstLeaf]))
            ->assertRedirect();
        $this->assertFalse($template->fresh()->is_active);

        $this->actingAs($user)
            ->patch(route('project-templates.toggle-status', $template), ['is_active' => 1])
            ->assertRedirect();

        $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
        ]))->assertRedirect();

        $project = Project::query()->firstOrFail();
        $runtimeRoot = Task::query()->where('project_id', $project->id)->where('name', $templateRoot->name)->firstOrFail();
        $remainingLeaf = Task::query()->where('project_id', $project->id)->where('name', 'Release')->firstOrFail();

        $this->assertSame('75.00', $runtimeRoot->weight);
        $this->assertSame('100.00', $remainingLeaf->subtask_weight_percentage);
        $remainingLeaf->update(['status' => 'completed']);
        $this->assertSame(100.0, $project->fresh()->calculateProgress());
    }

    public function test_runtime_task_changes_do_not_mutate_template_snapshot(): void
    {
        [$user, $workspace] = $this->workspaceOwner();
        [$template] = $this->activeTemplate($user);
        $this->actingAs($user)->post(route('projects.store'), $this->projectPayload($workspace, [
            'project_template_id' => $template->id,
        ]))->assertRedirect();

        $runtimeTask = Task::query()->where('name', 'Implementation')->firstOrFail();
        $runtimeTask->update(['name' => 'Runtime only']);

        $this->assertDatabaseHas('project_template_tasks', ['name' => 'Implementation']);
        $this->assertDatabaseMissing('project_template_tasks', ['name' => 'Runtime only']);
    }

    private function workspaceOwner(): array
    {
        $user = User::factory()->member()->create();
        $workspace = Workspace::factory()->for($user, 'creator')->create();

        return [$user, $workspace];
    }

    private function activeTemplate(User $user): array
    {
        $category = ProjectTemplateCategory::factory()->for($user, 'creator')->create(['is_active' => true]);
        $template = ProjectTemplate::factory()->for($category, 'category')->for($user, 'creator')->create([
            'name' => 'Delivery Blueprint',
            'version' => 3,
            'is_active' => true,
        ]);
        $root = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'name' => 'Delivery',
            'weight' => null,
            'position' => 0,
        ]);
        $firstLeaf = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $root->id,
            'name' => 'Implementation',
            'weight' => 50,
            'position' => 0,
            'start_offset_days' => 0,
            'duration_days' => 3,
        ]);
        $secondLeaf = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $root->id,
            'name' => 'Release',
            'weight' => 75,
            'position' => 1,
            'start_offset_days' => 1,
            'duration_days' => 5,
        ]);
        ProjectTemplateTaskDependency::query()->create([
            'project_template_id' => $template->id,
            'project_template_task_id' => $secondLeaf->id,
            'predecessor_template_task_id' => $firstLeaf->id,
            'dependency_type' => 'FS',
            'lag_days' => 3,
        ]);

        return [$template, $root, $firstLeaf, $secondLeaf];
    }

    private function projectPayload(Workspace $workspace, array $overrides = []): array
    {
        return array_merge([
            'workspace_id' => $workspace->id,
            'name' => 'Template Project',
            'description' => 'Created from tests.',
            'start_date' => '2026-07-20',
            'due_date' => '2026-08-10',
            'status' => 'planning',
            'project_template_id' => null,
        ], $overrides);
    }
}
