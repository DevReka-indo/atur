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
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectTemplateManagementTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createProjectTemplateTestSchema();
        DB::statement('PRAGMA foreign_keys = ON');
        DB::connection()->getPdo()->sqliteCreateFunction('FIELD', function ($value, ...$values): int {
            $position = array_search($value, $values, true);

            return $position === false ? 0 : $position + 1;
        });
    }

    public function test_permissions_and_template_metadata_crud_are_enforced(): void
    {
        $category = $this->category();
        $member = User::factory()->member()->create();
        $contributor = User::factory()->contributor()->create();

        $this->get(route('project-templates.index'))->assertRedirect(route('login'));
        $this->actingAs($member)->get(route('project-templates.index'))->assertForbidden();

        $this->actingAs($contributor)->post(route('project-templates.store'), [
            'project_template_category_id' => $category->id,
            'name' => 'Website Delivery',
            'description' => 'Initial description.',
        ])->assertRedirect();

        $template = ProjectTemplate::query()->where('slug', 'website-delivery')->firstOrFail();
        $this->assertFalse($template->is_active);
        $this->assertSame(1, $template->version);

        $this->actingAs($contributor)->put(route('project-templates.update', $template), [
            'project_template_category_id' => $category->id,
            'name' => 'Website Delivery Updated',
            'description' => 'Updated metadata.',
        ])->assertRedirect();

        $this->assertSame(1, $template->fresh()->version);
        $this->assertSame('website-delivery-updated', $template->fresh()->slug);
        $this->actingAs($contributor)->delete(route('project-templates.destroy', $template->fresh()))->assertForbidden();
    }

    public function test_template_slugs_are_unique_including_soft_deleted_rows(): void
    {
        $user = User::factory()->superAdmin()->create();
        $category = $this->category($user);
        ProjectTemplate::factory()->for($category, 'category')->for($user, 'creator')->create([
            'name' => 'Launch',
            'slug' => 'launch',
            'deleted_at' => now(),
        ]);

        $this->actingAs($user)->post(route('project-templates.store'), [
            'project_template_category_id' => $category->id,
            'name' => 'Launch',
        ])->assertRedirect();

        $this->assertDatabaseHas('project_templates', ['slug' => 'launch-2']);
    }

    public function test_activation_requires_an_active_category(): void
    {
        $user = User::factory()->contributor()->create();
        $inactiveCategory = $this->category($user, false);
        $template = ProjectTemplate::factory()->for($inactiveCategory, 'category')->for($user, 'creator')->create();
        ProjectTemplateTask::factory()->for($template, 'template')->create(['weight' => 75]);

        $this->actingAs($user)->patch(route('project-templates.toggle-status', $template), [
            'is_active' => 1,
        ])->assertSessionHasErrors('is_active');
        $this->assertFalse($template->fresh()->is_active);
    }

    #[DataProvider('relativeTotalWeightProvider')]
    public function test_positive_relative_total_leaf_weight_can_be_activated(float $totalWeight): void
    {
        $user = User::factory()->contributor()->create();
        $category = $this->category($user);
        $template = ProjectTemplate::factory()->for($category, 'category')->for($user, 'creator')->create();
        ProjectTemplateTask::factory()->for($template, 'template')->create(['weight' => $totalWeight]);

        $this->actingAs($user)->patch(route('project-templates.toggle-status', $template), [
            'is_active' => 1,
        ])->assertRedirect();

        $this->assertTrue($template->fresh()->is_active);
        $this->assertSame(1, $template->fresh()->version);
        $this->actingAs($user)
            ->get(route('project-templates.show', $template))
            ->assertOk()
            ->assertSee('Total Beban Template')
            ->assertSee(number_format($totalWeight, 2))
            ->assertDontSee('Total weight harus 100')
            ->assertSee('Preview Jadwal Relatif')
            ->assertSee('Hari ke-1');
    }

    public static function relativeTotalWeightProvider(): array
    {
        return [
            'total 75' => [75],
            'total 125' => [125],
            'total 250' => [250],
        ];
    }

    #[DataProvider('invalidLeafWeightProvider')]
    public function test_activation_rejects_non_positive_or_missing_leaf_weight(?float $weight): void
    {
        $user = User::factory()->contributor()->create();
        $category = $this->category($user);
        $template = ProjectTemplate::factory()->for($category, 'category')->for($user, 'creator')->create();
        ProjectTemplateTask::factory()->for($template, 'template')->create(['weight' => $weight]);

        $this->actingAs($user)->patch(route('project-templates.toggle-status', $template), [
            'is_active' => 1,
        ])->assertSessionHasErrors('weight');

        $this->assertFalse($template->fresh()->is_active);
    }

    public static function invalidLeafWeightProvider(): array
    {
        return [
            'zero' => [0],
            'null' => [null],
        ];
    }

    public function test_soft_delete_of_applied_template_preserves_project_runtime_graph_and_template_definition(): void
    {
        [$user, $workspace, $template, $project, $templateTasks, $dependency] = $this->appliedTemplateProject();
        $runtimeTasksBefore = $this->runtimeTaskGraph($project);

        $this->actingAs($user)->delete(route('project-templates.destroy', $template))->assertRedirect();

        $this->assertSoftDeleted($template);
        $this->assertModelExists($workspace);
        $this->assertModelExists($project);

        $projectAfterDelete = $project->fresh();
        $this->assertSame($template->id, $projectAfterDelete->project_template_id);
        $this->assertSame($template->name, $projectAfterDelete->source_template_name);
        $this->assertSame($template->version, $projectAfterDelete->source_template_version);
        $this->assertSame($runtimeTasksBefore, $this->runtimeTaskGraph($projectAfterDelete));

        foreach ($templateTasks as $templateTask) {
            $this->assertModelExists($templateTask);
        }

        $this->assertModelExists($dependency);
        $this->assertNotNull($projectAfterDelete->sourceTemplate);
        $this->assertTrue($projectAfterDelete->sourceTemplate->trashed());
    }

    public function test_force_delete_nulls_project_lineage_link_without_deleting_project_or_runtime_graph(): void
    {
        [$user, $workspace, $template, $project, $templateTasks, $dependency] = $this->appliedTemplateProject();
        $runtimeTasksBefore = $this->runtimeTaskGraph($project);
        $foreignKeys = DB::selectOne('PRAGMA foreign_keys');

        $this->assertSame(1, (int) $foreignKeys->foreign_keys);

        $this->actingAs($user)->delete(route('project-templates.destroy', $template))->assertRedirect();
        ProjectTemplate::withTrashed()->findOrFail($template->id)->forceDelete();

        $this->assertNull(ProjectTemplate::withTrashed()->find($template->id));
        $this->assertModelExists($workspace);
        $this->assertModelExists($project);

        $projectAfterDelete = $project->fresh();
        $this->assertNull($projectAfterDelete->project_template_id);
        $this->assertSame($template->name, $projectAfterDelete->source_template_name);
        $this->assertSame($template->version, $projectAfterDelete->source_template_version);
        $this->assertNull($projectAfterDelete->sourceTemplate);
        $this->assertSame($runtimeTasksBefore, $this->runtimeTaskGraph($projectAfterDelete));

        foreach ($templateTasks as $templateTask) {
            $this->assertModelMissing($templateTask);
        }

        $this->assertModelMissing($dependency);
    }

    private function category(?User $user = null, bool $active = true): ProjectTemplateCategory
    {
        $user ??= User::factory()->superAdmin()->create();

        return ProjectTemplateCategory::factory()->for($user, 'creator')->create(['is_active' => $active]);
    }

    /**
     * @return array{
     *     User,
     *     Workspace,
     *     ProjectTemplate,
     *     Project,
     *     array<int, ProjectTemplateTask>,
     *     ProjectTemplateTaskDependency
     * }
     */
    private function appliedTemplateProject(): array
    {
        $user = User::factory()->superAdmin()->create();
        $category = $this->category($user);
        $template = ProjectTemplate::factory()
            ->for($category, 'category')
            ->for($user, 'creator')
            ->create([
                'name' => 'Stable Snapshot',
                'version' => 7,
                'is_active' => true,
            ]);
        $root = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'name' => 'Delivery',
            'weight' => null,
            'position' => 0,
        ]);
        $analysis = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $root->id,
            'name' => 'Analysis',
            'weight' => 50,
            'position' => 0,
            'start_offset_days' => 0,
            'duration_days' => 3,
        ]);
        $implementation = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $root->id,
            'name' => 'Implementation',
            'weight' => 75,
            'position' => 1,
            'start_offset_days' => 0,
            'duration_days' => 2,
        ]);
        $dependency = ProjectTemplateTaskDependency::query()->create([
            'project_template_id' => $template->id,
            'project_template_task_id' => $implementation->id,
            'predecessor_template_task_id' => $analysis->id,
            'dependency_type' => 'FS',
            'lag_days' => 2,
        ]);
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        $workspace->members()->attach($user->id, [
            'role' => Workspace::ROLE_OWNER,
            'joined_at' => now(),
        ]);

        $this->actingAs($user)->post(route('projects.store'), [
            'workspace_id' => $workspace->id,
            'project_template_id' => $template->id,
            'name' => 'Project from Stable Snapshot',
            'description' => 'Runtime project used by template deletion tests.',
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-10',
            'status' => 'planning',
        ])->assertRedirect();

        $project = Project::query()->where('name', 'Project from Stable Snapshot')->firstOrFail();
        $this->assertSame($template->id, $project->project_template_id);
        $this->assertSame($template->name, $project->source_template_name);
        $this->assertSame($template->version, $project->source_template_version);
        $this->assertCount(3, $project->tasks);

        return [
            $user,
            $workspace,
            $template,
            $project,
            [$root, $analysis, $implementation],
            $dependency,
        ];
    }

    /**
     * @return array<int, array{parent_task_id: int|null, predecessor_id: int|null}>
     */
    private function runtimeTaskGraph(Project $project): array
    {
        return Task::query()
            ->whereBelongsTo($project)
            ->orderBy('id')
            ->get(['id', 'parent_task_id', 'predecessor_id'])
            ->mapWithKeys(fn (Task $task): array => [
                $task->id => [
                    'parent_task_id' => $task->parent_task_id,
                    'predecessor_id' => $task->predecessor_id,
                ],
            ])
            ->all();
    }
}
