<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectEditTest extends TestCase
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

    public function test_edit_page_renders_existing_project_values_and_form_contract(): void
    {
        [$owner, $workspace, $project] = $this->projectFixture();

        $response = $this->actingAs($owner)->get(route('projects.edit', $project->token));
        $content = $response->getContent();

        $response
            ->assertOk()
            ->assertSee('Workspace project tidak dapat diubah')
            ->assertSee($workspace->name)
            ->assertSee('value="'.$project->name.'"', false)
            ->assertSee('value="2026-08-01"', false)
            ->assertSee('value="2026-08-31"', false)
            ->assertSee($project->description)
            ->assertSee('action="'.route('projects.update', $project->token).'"', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('href="'.route('projects.show', $project->token).'"', false);

        $this->assertMatchesRegularExpression(
            '/<input\b(?=[^>]*\btype="hidden")(?=[^>]*\bname="workspace_id")(?=[^>]*\bvalue="'.$workspace->id.'")[^>]*>/i',
            $content,
        );
        $this->assertMatchesRegularExpression(
            '/<option\b[^>]*\bvalue="active"[^>]*\bselected(?:="selected")?[^>]*>/i',
            $content,
        );
    }

    public function test_edit_view_uses_only_the_edit_partials_and_has_no_inline_script(): void
    {
        $viewRoot = resource_path('views/projects');
        $editView = file_get_contents($viewRoot.'/edit.blade.php');
        $partialNames = [
            '_form-errors',
            '_project-information',
            '_project-timeline',
            '_project-description',
            '_form-actions',
        ];

        foreach ($partialNames as $partialName) {
            $partialPath = $viewRoot."/partials/edit/{$partialName}.blade.php";

            $this->assertStringContainsString("projects.partials.edit.{$partialName}", $editView);
            $this->assertFileExists($partialPath);
            $this->assertStringNotContainsString('<form', file_get_contents($partialPath));
        }

        $this->assertStringNotContainsString('<script', $editView);
        $this->assertSame(1, substr_count($editView, '<form'));
    }

    public function test_existing_project_update_flow_still_succeeds(): void
    {
        [$owner, $workspace, $project] = $this->projectFixture();

        $response = $this->actingAs($owner)->put(route('projects.update', $project->token), [
            'workspace_id' => $workspace->id,
            'name' => 'Updated Project Name',
            'description' => 'Updated project description.',
            'start_date' => '2026-09-01',
            'end_date' => '2026-10-15',
            'status' => 'urgent',
        ]);
        $project->refresh();
        $this->assertSame('2026-09-01', $project->start_date->toDateString());
        $this->assertSame('2026-10-15', $project->end_date->toDateString());

        $response->assertRedirect();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'workspace_id' => $workspace->id,
            'name' => 'Updated Project Name',
            'description' => 'Updated project description.',
            'status' => 'urgent',
        ]);
    }

    public function test_validation_errors_and_old_input_are_restored_on_edit_page(): void
    {
        [$owner, $workspace, $project] = $this->projectFixture();
        $invalidName = str_repeat('X', 256);

        $this->actingAs($owner)
            ->from(route('projects.edit', $project->token))
            ->put(route('projects.update', $project->token), [
                'workspace_id' => $workspace->id,
                'name' => $invalidName,
                'description' => 'Old validation description.',
                'start_date' => '2026-10-10',
                'end_date' => '2026-10-01',
                'status' => 'urgent',
            ])
            ->assertRedirect(route('projects.edit', $project->token))
            ->assertSessionHasErrors(['name', 'end_date']);

        $response = $this->get(route('projects.edit', $project->token));

        $response
            ->assertOk()
            ->assertSee('Beberapa data belum valid')
            ->assertSee('value="'.$invalidName.'"', false)
            ->assertSee('value="2026-10-10"', false)
            ->assertSee('value="2026-10-01"', false)
            ->assertSee('Old validation description.');
        $this->assertMatchesRegularExpression(
            '/<option\b[^>]*\bvalue="urgent"[^>]*\bselected(?:="selected")?[^>]*>/i',
            $response->getContent(),
        );
    }

    /**
     * @return array{User, Workspace, Project}
     */
    private function projectFixture(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->for($owner, 'creator')->create([
            'name' => 'Read Only Workspace',
        ]);
        $project = Project::factory()
            ->for($workspace)
            ->for($owner, 'creator')
            ->create([
                'name' => 'Existing Project',
                'description' => 'Existing project description.',
                'start_date' => '2026-08-01',
                'end_date' => '2026-08-31',
                'status' => 'active',
            ]);

        return [$owner, $workspace, $project];
    }
}
