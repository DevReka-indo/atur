<?php

namespace Tests\Feature;

use App\Models\ProjectTemplate;
use App\Models\ProjectTemplateCategory;
use App\Models\User;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectTemplateCategoryTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->createProjectTemplateTestSchema();
    }

    public function test_category_routes_enforce_guest_member_and_contributor_permissions(): void
    {
        $this->get(route('project-template-categories.index'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->member()->create())
            ->get(route('project-template-categories.index'))
            ->assertForbidden();

        $contributor = User::factory()->contributor()->create();
        $this->actingAs($contributor)->get(route('project-template-categories.index'))->assertOk();
        $this->actingAs($contributor)->get(route('project-template-categories.create'))->assertOk();
    }

    public function test_contributor_can_create_update_and_deactivate_but_cannot_delete(): void
    {
        $contributor = User::factory()->contributor()->create();

        $storeResponse = $this->actingAs($contributor)->post(route('project-template-categories.store'), [
            'name' => 'Software Delivery',
            'description' => 'Reusable delivery category.',
            'is_active' => true,
        ]);
        $storeResponse
            ->assertRedirect(route('project-template-categories.index'))
            ->assertSessionHas('success', 'Kategori template berhasil dibuat.');

        $category = ProjectTemplateCategory::query()->firstOrFail();
        $this->assertSame('software-delivery', $category->slug);
        $this->assertTrue($category->is_active);

        $updateResponse = $this->actingAs($contributor)->put(route('project-template-categories.update', $category), [
            'name' => 'Software Projects',
            'description' => 'Updated.',
        ]);
        $updateResponse
            ->assertRedirect(route('project-template-categories.index'))
            ->assertSessionHas('success', 'Kategori template berhasil diperbarui.');
        $category = $category->fresh();
        $this->assertSame('software-projects', $category->slug);

        $this->actingAs($contributor)
            ->from(route('project-template-categories.index'))
            ->patch(route('project-template-categories.toggle-status', $category))
            ->assertRedirect(route('project-template-categories.index'))
            ->assertSessionHas('success');
        $this->assertFalse($category->fresh()->is_active);

        $this->actingAs($contributor)
            ->withHeader('referer', 'https://external.example/categories')
            ->patch(route('project-template-categories.toggle-status', $category))
            ->assertRedirect(route('project-template-categories.index'));
        $this->assertTrue($category->fresh()->is_active);

        $this->actingAs($contributor)
            ->delete(route('project-template-categories.destroy', $category))
            ->assertForbidden();
    }

    public function test_validation_failures_return_to_category_forms_with_old_input_without_mutating_data(): void
    {
        $contributor = User::factory()->contributor()->create();

        $this->actingAs($contributor)
            ->from(route('project-template-categories.create'))
            ->post(route('project-template-categories.store'), [
                'name' => '',
                'description' => 'Input create dipertahankan.',
            ])
            ->assertRedirect(route('project-template-categories.create'))
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('description', 'Input create dipertahankan.');
        $this->assertDatabaseCount('project_template_categories', 0);

        $category = ProjectTemplateCategory::factory()->for($contributor, 'creator')->create([
            'name' => 'Nama Awal',
            'slug' => 'nama-awal',
            'description' => 'Deskripsi awal.',
        ]);

        $this->actingAs($contributor)
            ->from(route('project-template-categories.edit', $category))
            ->put(route('project-template-categories.update', $category), [
                'name' => '',
                'description' => 'Input edit dipertahankan.',
            ])
            ->assertRedirect(route('project-template-categories.edit', $category))
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('description', 'Input edit dipertahankan.');

        $this->assertSame('Nama Awal', $category->fresh()->name);
        $this->assertSame('Deskripsi awal.', $category->fresh()->description);
    }

    public function test_slug_conflicts_include_soft_deleted_rows(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        ProjectTemplateCategory::factory()->for($superAdmin, 'creator')->create([
            'name' => 'Operations',
            'slug' => 'operations',
            'deleted_at' => now(),
        ]);

        $this->actingAs($superAdmin)->post(route('project-template-categories.store'), [
            'name' => 'Operations',
        ])->assertRedirect();

        $this->assertDatabaseHas('project_template_categories', ['slug' => 'operations-2']);
    }

    public function test_super_admin_can_delete_unused_category_but_not_one_with_trashed_template(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $unused = ProjectTemplateCategory::factory()->for($superAdmin, 'creator')->create();

        $this->actingAs($superAdmin)
            ->delete(route('project-template-categories.destroy', $unused))
            ->assertRedirect(route('project-template-categories.index'));
        $this->assertSoftDeleted($unused);

        $used = ProjectTemplateCategory::factory()->for($superAdmin, 'creator')->create();
        ProjectTemplate::factory()->for($used, 'category')->for($superAdmin, 'creator')->create([
            'deleted_at' => now(),
        ]);

        $this->actingAs($superAdmin)
            ->delete(route('project-template-categories.destroy', $used))
            ->assertSessionHasErrors('category');
        $this->assertNotSoftDeleted($used);
    }
}
