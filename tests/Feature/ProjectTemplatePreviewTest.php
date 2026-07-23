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

class ProjectTemplatePreviewTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createProjectTemplateTestSchema();
        DB::connection()->getPdo()->sqliteCreateFunction(
            'FIELD',
            static function (mixed $value, mixed ...$values): int {
                $position = array_search($value, $values, true);

                return $position === false ? 0 : $position + 1;
            },
            -1,
        );
    }

    public function test_guest_cannot_access_template_preview(): void
    {
        [$template] = $this->activeTemplate();

        $this->get($this->previewUrl($template))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_template_management_permission_can_preview_active_template(): void
    {
        $user = User::factory()->member()->create();
        [$template] = $this->activeTemplate();

        $this->assertFalse($user->can('project-templates.view'));
        $projectsBefore = Project::query()->count();
        $runtimeTasksBefore = Task::query()->count();

        $response = $this->actingAs($user)->getJson($this->previewUrl($template, [
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-10',
        ]));

        $response
            ->assertOk()
            ->assertJsonStructure([
                'id',
                'name',
                'category',
                'description',
                'version',
                'summary' => [
                    'tasks_count',
                    'root_tasks_count',
                    'leaf_tasks_count',
                    'hierarchy_levels',
                    'total_leaf_weight',
                    'duration_days',
                ],
                'timeline' => [
                    'project_start_date',
                    'requested_due_date',
                    'estimated_end_date',
                    'will_extend_project',
                ],
                'tasks',
            ])
            ->assertJsonPath('summary.tasks_count', 7)
            ->assertJsonPath('summary.root_tasks_count', 4)
            ->assertJsonPath('summary.leaf_tasks_count', 5)
            ->assertJsonPath('summary.hierarchy_levels', 3)
            ->assertJsonPath('summary.total_leaf_weight', 125)
            ->assertJsonPath('summary.duration_days', 12)
            ->assertJsonPath('timeline.estimated_end_date', '2026-09-12')
            ->assertJsonPath('timeline.will_extend_project', true)
            ->assertJsonPath('tasks.0.name', 'Delivery')
            ->assertJsonPath('tasks.0.is_leaf', false)
            ->assertJsonPath('tasks.0.weight', null)
            ->assertJsonPath('tasks.0.aggregate_weight', 50)
            ->assertJsonPath('tasks.0.children.0.name', 'Build')
            ->assertJsonPath('tasks.0.children.0.children.0.name', 'Analysis')
            ->assertJsonPath('tasks.0.children.0.children.1.predecessor.dependency_type', 'FS')
            ->assertJsonPath('tasks.0.children.0.children.1.predecessor.lag_days', 2)
            ->assertJsonPath('tasks.1.predecessor.dependency_type', 'SS')
            ->assertJsonPath('tasks.1.predecessor.name', 'Implementation')
            ->assertJsonPath('tasks.1.predecessor.lag_days', 1)
            ->assertJsonPath('tasks.2.predecessor.dependency_type', 'FF')
            ->assertJsonPath('tasks.2.predecessor.lag_days', 2)
            ->assertJsonPath('tasks.3.predecessor.dependency_type', 'SF')
            ->assertJsonPath('tasks.3.predecessor.lag_days', 1);

        $this->assertSame($projectsBefore, Project::query()->count());
        $this->assertSame($runtimeTasksBefore, Task::query()->count());
    }

    public function test_inactive_template_or_category_cannot_be_previewed(): void
    {
        $user = User::factory()->member()->create();
        [$template] = $this->activeTemplate();
        $template->update(['is_active' => false]);

        $this->actingAs($user)->getJson($this->previewUrl($template))->assertNotFound();

        $template->update(['is_active' => true]);
        $template->category()->update(['is_active' => false]);

        $this->actingAs($user)->getJson($this->previewUrl($template))->assertNotFound();
    }

    public function test_timeline_extension_flag_reflects_requested_due_date(): void
    {
        $user = User::factory()->member()->create();
        [$template] = $this->activeTemplate();

        $this->actingAs($user)
            ->getJson($this->previewUrl($template, [
                'start_date' => '2026-09-01',
                'due_date' => '2026-09-12',
            ]))
            ->assertOk()
            ->assertJsonPath('timeline.will_extend_project', false);

        $this->actingAs($user)
            ->getJson($this->previewUrl($template, [
                'start_date' => '2026-09-01',
                'due_date' => '2026-09-20',
            ]))
            ->assertOk()
            ->assertJsonPath('timeline.will_extend_project', false);
    }

    public function test_preview_accepts_a_due_date_before_start_date_is_filled(): void
    {
        $user = User::factory()->member()->create();
        [$template] = $this->activeTemplate();

        $this->actingAs($user)
            ->getJson($this->previewUrl($template, ['due_date' => '2026-09-20']))
            ->assertOk()
            ->assertJsonPath('timeline.project_start_date', null)
            ->assertJsonPath('timeline.requested_due_date', '2026-09-20')
            ->assertJsonPath('timeline.estimated_end_date', null)
            ->assertJsonPath('timeline.will_extend_project', false);
    }

    public function test_invalid_preview_dates_return_validation_errors(): void
    {
        $user = User::factory()->member()->create();
        [$template] = $this->activeTemplate();

        $this->actingAs($user)
            ->getJson($this->previewUrl($template, ['start_date' => 'not-a-date']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('start_date');

        $this->actingAs($user)
            ->getJson($this->previewUrl($template, [
                'start_date' => '2026-09-10',
                'due_date' => '2026-09-01',
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('due_date');
    }

    public function test_create_page_prefers_old_template_then_query_and_ignores_inactive_selection(): void
    {
        $user = User::factory()->member()->create();
        [$queryTemplate] = $this->activeTemplate();
        [$oldTemplate] = $this->activeTemplate();

        $this->actingAs($user)
            ->get(route('projects.create', ['project_template_id' => $queryTemplate->id]))
            ->assertOk()
            ->assertViewHas('selectedProjectTemplateId', $queryTemplate->id)
            ->assertSee('data-preview-url=', false)
            ->assertDontSee('data-template=', false);

        $this->actingAs($user)
            ->withSession(['_old_input' => ['project_template_id' => $oldTemplate->id]])
            ->get(route('projects.create', ['project_template_id' => $queryTemplate->id]))
            ->assertOk()
            ->assertViewHas('selectedProjectTemplateId', $oldTemplate->id);

        $queryTemplate->update(['is_active' => false]);

        $this->actingAs($user)
            ->get(route('projects.create', ['project_template_id' => $queryTemplate->id]))
            ->assertOk()
            ->assertViewHas('selectedProjectTemplateId', null)
            ->assertViewHas(
                'projectTemplates',
                fn ($templates): bool => ! $templates->contains(
                    fn (array $template): bool => $template['id'] === $queryTemplate->id
                )
            );
    }

    public function test_preview_dates_match_applied_runtime_tasks_and_existing_creation_flows_remain_intact(): void
    {
        $user = User::factory()->member()->create();
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        [$template] = $this->activeTemplate();

        $preview = $this->actingAs($user)->getJson($this->previewUrl($template, [
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-10',
        ]));
        $preview->assertOk();

        $this->actingAs($user)
            ->post(route('projects.store'), $this->projectPayload($workspace, [
                'name' => 'From Preview',
                'project_template_id' => $template->id,
                'due_date' => '2026-09-10',
            ]))
            ->assertRedirect();

        $templatedProject = Project::query()->where('name', 'From Preview')->firstOrFail();
        $this->assertSame('2026-09-12', $templatedProject->end_date->toDateString());
        $this->assertCount(7, $templatedProject->tasks);
        $this->assertDatabaseMissing('tasks', [
            'project_id' => $templatedProject->id,
            'name' => 'Project Kickoff',
        ]);

        foreach ($this->flattenPreviewTasks($preview->json('tasks')) as $previewTask) {
            $runtimeTask = $templatedProject->tasks()->where('name', $previewTask['name'])->firstOrFail();
            $this->assertSame($previewTask['start_date'], $runtimeTask->start_date->toDateString());
            $this->assertSame($previewTask['due_date'], $runtimeTask->due_date->toDateString());
        }

        $this->actingAs($user)
            ->post(route('projects.store'), $this->projectPayload($workspace, ['name' => 'Without Template']))
            ->assertRedirect();

        $defaultProject = Project::query()->where('name', 'Without Template')->firstOrFail();
        $this->assertNull($defaultProject->project_template_id);
        $this->assertCount(6, $defaultProject->tasks);
    }

    /**
     * @return array{ProjectTemplate, array<string, ProjectTemplateTask>}
     */
    private function activeTemplate(): array
    {
        $owner = User::factory()->superAdmin()->create();
        $category = ProjectTemplateCategory::factory()->for($owner, 'creator')->create([
            'name' => 'Software Development',
            'is_active' => true,
        ]);
        $template = ProjectTemplate::factory()->for($category, 'category')->for($owner, 'creator')->create([
            'name' => 'Website Development',
            'description' => 'Template delivery website bertingkat.',
            'version' => 3,
            'is_active' => true,
        ]);

        $delivery = $this->templateTask($template, 'Delivery', null, 0, null, 0, 1);
        $build = $this->templateTask($template, 'Build', $delivery, 0, null, 0, 1);
        $analysis = $this->templateTask($template, 'Analysis', $build, 0, 20, 0, 3);
        $implementation = $this->templateTask($template, 'Implementation', $build, 1, 30, 1, 2);
        $qualityAssurance = $this->templateTask($template, 'Quality Assurance', null, 1, 25, 2, 4);
        $release = $this->templateTask($template, 'Release', null, 2, 25, 3, 2);
        $handover = $this->templateTask($template, 'Handover', null, 3, 25, 4, 3);

        $this->dependency($template, $implementation, $analysis, 'FS', 2);
        $this->dependency($template, $qualityAssurance, $implementation, 'SS', 1);
        $this->dependency($template, $release, $qualityAssurance, 'FF', 2);
        $this->dependency($template, $handover, $release, 'SF', 1);

        return [$template, compact(
            'delivery',
            'build',
            'analysis',
            'implementation',
            'qualityAssurance',
            'release',
            'handover',
        )];
    }

    private function templateTask(
        ProjectTemplate $template,
        string $name,
        ?ProjectTemplateTask $parent,
        int $position,
        ?float $weight,
        int $startOffset,
        int $duration,
    ): ProjectTemplateTask {
        return ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $parent?->id,
            'name' => $name,
            'position' => $position,
            'weight' => $weight,
            'start_offset_days' => $startOffset,
            'duration_days' => $duration,
        ]);
    }

    private function dependency(
        ProjectTemplate $template,
        ProjectTemplateTask $task,
        ProjectTemplateTask $predecessor,
        string $type,
        int $lagDays,
    ): void {
        ProjectTemplateTaskDependency::query()->create([
            'project_template_id' => $template->id,
            'project_template_task_id' => $task->id,
            'predecessor_template_task_id' => $predecessor->id,
            'dependency_type' => $type,
            'lag_days' => $lagDays,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $tasks
     * @return array<int, array<string, mixed>>
     */
    private function flattenPreviewTasks(array $tasks): array
    {
        $flattened = [];

        foreach ($tasks as $task) {
            $children = $task['children'];
            unset($task['children']);
            $flattened[] = $task;
            array_push($flattened, ...$this->flattenPreviewTasks($children));
        }

        return $flattened;
    }

    /**
     * @param  array<string, string>  $query
     */
    private function previewUrl(ProjectTemplate $template, array $query = []): string
    {
        return route('project-templates.preview', array_merge([
            'projectTemplate' => $template->id,
        ], $query));
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function projectPayload(Workspace $workspace, array $overrides = []): array
    {
        return array_merge([
            'workspace_id' => $workspace->id,
            'name' => 'Preview Project',
            'description' => 'Project template preview test.',
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-20',
            'status' => 'planning',
            'project_template_id' => null,
        ], $overrides);
    }
}
