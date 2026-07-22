<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Models\ProjectTemplateTask;
use App\Models\User;
use App\Models\Workspace;
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

    public function test_super_admin_soft_delete_preserves_project_lineage_snapshot(): void
    {
        $user = User::factory()->superAdmin()->create();
        $category = $this->category($user);
        $template = ProjectTemplate::factory()->for($category, 'category')->for($user, 'creator')->create([
            'name' => 'Stable Snapshot',
            'version' => 7,
        ]);
        $workspace = Workspace::factory()->for($user, 'creator')->create();
        $project = Project::factory()->for($workspace)->for($user, 'creator')->create([
            'project_template_id' => $template->id,
            'source_template_name' => $template->name,
            'source_template_version' => $template->version,
        ]);

        $this->actingAs($user)->delete(route('project-templates.destroy', $template))->assertRedirect();

        $this->assertSoftDeleted($template);
        $this->assertSame('Stable Snapshot', $project->fresh()->source_template_name);
        $this->assertSame(7, $project->fresh()->source_template_version);
        $this->assertTrue($project->fresh()->sourceTemplate->trashed());
    }

    private function category(?User $user = null, bool $active = true): ProjectTemplateCategory
    {
        $user ??= User::factory()->superAdmin()->create();

        return ProjectTemplateCategory::factory()->for($user, 'creator')->create(['is_active' => $active]);
    }
}
