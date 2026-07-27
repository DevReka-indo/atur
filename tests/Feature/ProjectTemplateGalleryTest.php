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
use Illuminate\Support\Facades\Route;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectTemplateGalleryTest extends TestCase
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

    public function test_guest_cannot_open_gallery_index_or_detail(): void
    {
        [$template] = $this->createTemplate();

        $this->get(route('template-gallery.index'))->assertRedirect(route('login'));
        $this->get(route('template-gallery.show', $template))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_only_usable_templates_sidebar_and_read_only_actions(): void
    {
        $user = User::factory()->member()->create();
        [$activeTemplate] = $this->createTemplate(['name' => 'Active Blueprint']);
        [$inactiveTemplate] = $this->createTemplate([
            'name' => 'Inactive Blueprint',
            'is_active' => false,
        ]);
        [$inactiveCategoryTemplate] = $this->createTemplate(
            ['name' => 'Hidden Category Blueprint'],
            ['is_active' => false],
        );
        $projectsBefore = Project::query()->count();
        $runtimeTasksBefore = Task::query()->count();

        $this->actingAs($user)
            ->get(route('template-gallery.index'))
            ->assertOk()
            ->assertSee('Template Gallery')
            ->assertSee($activeTemplate->name)
            ->assertDontSee($inactiveTemplate->name)
            ->assertDontSee($inactiveCategoryTemplate->name)
            ->assertSee('Lihat Detail')
            ->assertSee('Gunakan Template')
            ->assertDontSee('Buat Template')
            ->assertDontSee('Edit Metadata')
            ->assertDontSee('Hapus Template')
            ->assertDontSee('Aktifkan Template');

        $this->assertSame($projectsBefore, Project::query()->count());
        $this->assertSame($runtimeTasksBefore, Task::query()->count());
    }

    public function test_gallery_modal_uses_existing_project_store_and_only_lists_authorized_workspaces(): void
    {
        $user = User::factory()->member()->create();
        $allowedWorkspace = Workspace::factory()->for($user, 'creator')->create(['name' => 'Allowed Workspace']);
        $allowedWorkspace->members()->attach($user->id, ['role' => Workspace::ROLE_OWNER]);
        $blockedWorkspace = Workspace::factory()->create(['name' => 'Blocked Workspace']);
        $blockedWorkspace->members()->attach($user->id, ['role' => Workspace::ROLE_MEMBER]);
        [$template] = $this->createTemplate(['name' => 'Modal Blueprint']);

        $this->actingAs($user)
            ->get(route('template-gallery.index'))
            ->assertOk()
            ->assertViewHas('workspaces', fn ($workspaces): bool => $workspaces->pluck('id')->all() === [
                $allowedWorkspace->id,
            ])
            ->assertSee('id="use-template-modal"', false)
            ->assertSee('action="'.route('projects.store').'"', false)
            ->assertSee('name="project_template_id"', false)
            ->assertSee('name="workspace_id"', false)
            ->assertSee('name="name"', false)
            ->assertSee('name="start_date"', false)
            ->assertSee('name="due_date"', false)
            ->assertSee('name="status"', false)
            ->assertSee('name="description"', false)
            ->assertSee($allowedWorkspace->name)
            ->assertSee('data-gallery-workspace-id="'.$allowedWorkspace->id.'"', false)
            ->assertDontSee('data-gallery-workspace-id="'.$blockedWorkspace->id.'"', false)
            ->assertSee(route('projects.create', ['project_template_id' => $template->id]), false)
            ->assertSee('data-use-template', false)
            ->assertSee('data-project-start-date', false)
            ->assertSee('data-project-due-date', false);

        $this->assertSame('POST', Route::getRoutes()->getByName('projects.store')->methods()[0]);
        $this->assertNull(Route::getRoutes()->getByName('template-gallery.store'));
    }

    public function test_authenticated_user_can_open_detail_with_hierarchy_dependency_and_use_link(): void
    {
        $user = User::factory()->member()->create();
        [$template, $tasks] = $this->createTemplate(['name' => 'Delivery Blueprint']);
        $projectsBefore = Project::query()->count();
        $runtimeTasksBefore = Task::query()->count();

        $this->actingAs($user)
            ->get(route('template-gallery.show', $template))
            ->assertOk()
            ->assertSee($template->name)
            ->assertSee($tasks['root']->name)
            ->assertSee($tasks['firstLeaf']->name)
            ->assertSee($tasks['secondLeaf']->name)
            ->assertSeeInOrder(['FS dari', $tasks['firstLeaf']->name, 'Lag', '2 hari'])
            ->assertSee('Weight 50.00')
            ->assertSee('Beban turunan 125.00')
            ->assertSee(route('projects.create', ['project_template_id' => $template->id]), false)
            ->assertSee('id="use-template-modal"', false)
            ->assertSee('action="'.route('projects.store').'"', false)
            ->assertSee('data-template-name="'.$template->name.'"', false)
            ->assertSee('Kembali ke Gallery')
            ->assertDontSee('Edit Metadata')
            ->assertDontSee('Hapus Template');

        $this->assertSame($projectsBefore, Project::query()->count());
        $this->assertSame($runtimeTasksBefore, Task::query()->count());
    }

    public function test_validation_error_reopens_modal_and_restores_old_project_input(): void
    {
        $user = User::factory()->member()->create();
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        $workspace->members()->attach($user->id, ['role' => Workspace::ROLE_OWNER]);
        [$template] = $this->createTemplate(['name' => 'Restored Blueprint']);
        $galleryUrl = route('template-gallery.index', ['search' => 'Restored', 'page' => 2]);

        $this->actingAs($user)
            ->from($galleryUrl)
            ->post(route('projects.store'), [
                'workspace_id' => $workspace->id,
                'project_template_id' => $template->id,
                'name' => 'Restored Project',
                'description' => 'Input lama tetap tersedia.',
                'start_date' => '2026-08-20',
                'due_date' => '2026-08-10',
                'status' => 'active',
            ])
            ->assertRedirect($galleryUrl)
            ->assertSessionHasErrors('due_date');

        $response = $this->get($galleryUrl);

        $response
            ->assertOk()
            ->assertSee('data-reopen="true"', false)
            ->assertSee('value="'.$template->id.'"', false)
            ->assertSee('value="Restored Project"', false)
            ->assertSee('Input lama tetap tersedia.')
            ->assertSee('value="2026-08-20"', false)
            ->assertSee('value="2026-08-10"', false)
            ->assertSee('min="2026-08-20"', false)
            ->assertSee('name="status"', false)
            ->assertSee('The due date field must be a date after or equal to start date.');

        $this->assertMatchesRegularExpression(
            '/<option\b(?=[^>]*\bvalue\s*=\s*["\']active["\'])(?=[^>]*\bselected(?:\s*=\s*["\']selected["\'])?)[^>]*>/i',
            $response->getContent(),
        );
        $this->assertDatabaseCount('projects', 0);
    }

    public function test_gallery_modal_payload_creates_project_with_template_through_existing_flow(): void
    {
        $user = User::factory()->member()->create();
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        $workspace->members()->attach($user->id, ['role' => Workspace::ROLE_OWNER]);
        [$template] = $this->createTemplate(['name' => 'Applied Blueprint']);

        $response = $this->actingAs($user)->post(route('projects.store'), [
            'workspace_id' => $workspace->id,
            'project_template_id' => $template->id,
            'name' => 'Project from Gallery',
            'description' => 'Created from the Gallery modal.',
            'start_date' => '2026-08-01',
            'due_date' => '2026-08-01',
            'status' => 'planning',
        ]);

        $project = Project::query()->where('name', 'Project from Gallery')->firstOrFail();
        $response->assertRedirect(route('projects.show', $project->token));
        $this->assertSame($template->id, $project->project_template_id);
        $this->assertSame('2026-08-07', $project->end_date->toDateString());
        $this->assertCount(3, $project->tasks);
        $this->assertDatabaseMissing('tasks', [
            'project_id' => $project->id,
            'name' => 'Project Kickoff',
        ]);

        $this->actingAs($user)
            ->get(route('projects.create', ['project_template_id' => $template->id]))
            ->assertOk()
            ->assertViewHas('selectedProjectTemplateId', $template->id);
    }

    public function test_unusable_templates_return_not_found_on_detail(): void
    {
        $user = User::factory()->member()->create();
        [$inactiveTemplate] = $this->createTemplate(['is_active' => false]);
        [$inactiveCategoryTemplate] = $this->createTemplate([], ['is_active' => false]);

        $this->actingAs($user)
            ->get(route('template-gallery.show', $inactiveTemplate))
            ->assertNotFound();
        $this->actingAs($user)
            ->get(route('template-gallery.show', $inactiveCategoryTemplate))
            ->assertNotFound();
    }

    public function test_search_matches_template_name_description_and_category(): void
    {
        $user = User::factory()->member()->create();
        [$nameMatch] = $this->createTemplate([
            'name' => 'Website Launch',
            'description' => 'Standard delivery.',
        ], ['name' => 'Software']);
        [$descriptionMatch] = $this->createTemplate([
            'name' => 'Campaign Plan',
            'description' => 'Includes discovery workshop.',
        ], ['name' => 'Marketing']);
        [$categoryMatch] = $this->createTemplate([
            'name' => 'Quarterly Review',
            'description' => 'Standard review.',
        ], ['name' => 'Operations']);

        $this->actingAs($user)
            ->get(route('template-gallery.index', ['search' => 'Website']))
            ->assertOk()
            ->assertSee($nameMatch->name)
            ->assertDontSee($descriptionMatch->name)
            ->assertDontSee($categoryMatch->name);

        $this->actingAs($user)
            ->get(route('template-gallery.index', ['search' => 'discovery']))
            ->assertOk()
            ->assertSee($descriptionMatch->name)
            ->assertDontSee($nameMatch->name);

        $this->actingAs($user)
            ->get(route('template-gallery.index', ['search' => 'Operations']))
            ->assertOk()
            ->assertSee($categoryMatch->name)
            ->assertDontSee($nameMatch->name);
    }

    public function test_category_filter_only_returns_templates_from_selected_active_category(): void
    {
        $user = User::factory()->member()->create();
        [$softwareTemplate] = $this->createTemplate(['name' => 'Software Template'], ['name' => 'Software']);
        [$marketingTemplate] = $this->createTemplate(['name' => 'Marketing Template'], ['name' => 'Marketing']);

        $this->actingAs($user)
            ->get(route('template-gallery.index', ['category' => $softwareTemplate->category->id]))
            ->assertOk()
            ->assertSee($softwareTemplate->name)
            ->assertDontSee($marketingTemplate->name);
    }

    public function test_pagination_has_twelve_items_and_preserves_search_and_category_queries(): void
    {
        $user = User::factory()->member()->create();
        $owner = User::factory()->superAdmin()->create();
        $category = ProjectTemplateCategory::factory()->for($owner, 'creator')->create([
            'name' => 'Delivery',
            'is_active' => true,
        ]);

        foreach (range(1, 13) as $index) {
            $template = ProjectTemplate::factory()
                ->for($category, 'category')
                ->for($owner, 'creator')
                ->create([
                    'name' => 'Delivery Template '.str_pad((string) $index, 2, '0', STR_PAD_LEFT),
                    'is_active' => true,
                ]);
            ProjectTemplateTask::factory()->for($template, 'template')->create(['weight' => 10]);
        }

        $this->actingAs($user)
            ->get(route('template-gallery.index', [
                'search' => 'Delivery Template',
                'category' => $category->id,
            ]))
            ->assertOk()
            ->assertViewHas('templates', function ($templates) use ($category): bool {
                return $templates->count() === 12
                    && $templates->total() === 13
                    && str_contains($templates->url(2), 'search=Delivery%20Template')
                    && str_contains($templates->url(2), 'category='.$category->id);
            });
    }

    public function test_card_summary_comes_from_preview_calculation(): void
    {
        $user = User::factory()->member()->create();
        [$template] = $this->createTemplate(['name' => 'Summary Blueprint']);

        $this->actingAs($user)
            ->get(route('template-gallery.index'))
            ->assertOk()
            ->assertViewHas('summaries', function ($summaries) use ($template): bool {
                $summary = $summaries->get($template->id);

                return $summary['tasks_count'] === 3
                    && $summary['root_tasks_count'] === 1
                    && $summary['leaf_tasks_count'] === 2
                    && $summary['hierarchy_levels'] === 2
                    && (float) $summary['total_leaf_weight'] === 125.0
                    && $summary['duration_days'] === 7;
            })
            ->assertSee('125.00')
            ->assertSee('7 hari');
    }

    /**
     * @param  array<string, mixed>  $templateOverrides
     * @param  array<string, mixed>  $categoryOverrides
     * @return array{ProjectTemplate, array<string, ProjectTemplateTask>}
     */
    private function createTemplate(
        array $templateOverrides = [],
        array $categoryOverrides = [],
    ): array {
        $owner = User::factory()->superAdmin()->create();
        $category = ProjectTemplateCategory::factory()->for($owner, 'creator')->create(array_merge([
            'is_active' => true,
        ], $categoryOverrides));
        $template = ProjectTemplate::factory()
            ->for($category, 'category')
            ->for($owner, 'creator')
            ->create(array_merge([
                'description' => 'Template project dengan hierarchy dan dependency.',
                'version' => 2,
                'is_active' => true,
            ], $templateOverrides));
        $root = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'name' => 'Delivery',
            'weight' => null,
            'position' => 0,
            'start_offset_days' => 0,
            'duration_days' => 1,
        ]);
        $firstLeaf = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $root->id,
            'name' => 'Analysis',
            'weight' => 50,
            'position' => 0,
            'start_offset_days' => 0,
            'duration_days' => 3,
        ]);
        $secondLeaf = ProjectTemplateTask::factory()->for($template, 'template')->create([
            'parent_id' => $root->id,
            'name' => 'Implementation',
            'weight' => 75,
            'position' => 1,
            'start_offset_days' => 0,
            'duration_days' => 2,
        ]);
        ProjectTemplateTaskDependency::query()->create([
            'project_template_id' => $template->id,
            'project_template_task_id' => $secondLeaf->id,
            'predecessor_template_task_id' => $firstLeaf->id,
            'dependency_type' => 'FS',
            'lag_days' => 2,
        ]);

        return [$template, compact('root', 'firstLeaf', 'secondLeaf')];
    }
}
