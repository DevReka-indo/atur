<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class ProjectMemberUxTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProjectTemplateTestSchema();
        Schema::create('project_threads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('user_id');
            $table->string('title');
            $table->text('body')->nullable();
            $table->timestamps();
        });
        Schema::create('project_thread_messages', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_thread_id');
            $table->foreignId('user_id');
            $table->text('content');
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });
        Schema::create('thread_user_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id');
            $table->foreignId('user_id');
            $table->timestamp('last_read_at')->nullable();
            $table->foreignId('last_read_message_id')->nullable();
            $table->timestamps();
            $table->unique(['thread_id', 'user_id']);
        });
        DB::connection()->getPdo()->sqliteCreateFunction(
            'FIELD',
            static function (mixed $value, mixed ...$values): int {
                $position = array_search($value, $values, true);

                return $position === false ? 0 : $position + 1;
            },
            -1,
        );
    }

    public function test_project_member_tab_uses_consistent_labels_and_filtered_workspace_candidates(): void
    {
        $fixture = $this->memberFixture();
        $manager = $fixture['manager'];
        $project = $fixture['project'];

        $response = $this->actingAs($manager)->get(route('projects.show', [
            'token' => $project->token,
            'tab' => 'members',
        ]));
        $content = $response->getContent();

        $response
            ->assertOk()
            ->assertSee('Project Members')
            ->assertSee('Manage project access and roles.')
            ->assertSee('Project Admins')
            ->assertSee('Members')
            ->assertSee('Viewers')
            ->assertSee('Invite Member')
            ->assertSee('data-member-invite-modal', false)
            ->assertSee('value="manager"', false)
            ->assertSee('Project Admin')
            ->assertSee('Workspace Owner')
            ->assertSee('You');

        $this->assertDoesNotMatchRegularExpression('/>\s*Manager\s*</', $content);
        $this->assertMatchesRegularExpression(
            '/<option\b[^>]*\bvalue="manager"[^>]*>\s*Project Admin\s*<\/option>/i',
            $content,
        );
        $this->assertMatchesRegularExpression(
            '/<select\b[^>]*\bid="project-member-role-'.$fixture['existingMember']->id.'"[^>]*>.*?<option\b[^>]*\bvalue="member"[^>]*\bselected(?:="selected")?/is',
            $content,
        );
        $this->assertStringContainsString(
            'data-member-id="'.$fixture['candidate']->id.'"',
            $content,
        );
        $this->assertStringNotContainsString(
            'data-member-id="'.$fixture['existingMember']->id.'"',
            $content,
        );
        $this->assertStringNotContainsString(
            'data-member-id="'.$fixture['outsider']->id.'"',
            $content,
        );
    }

    public function test_member_without_management_access_cannot_see_member_actions_or_invite_modal(): void
    {
        $fixture = $this->memberFixture();

        $response = $this->actingAs($fixture['existingMember'])->get(route('projects.show', [
            'token' => $fixture['project']->token,
            'tab' => 'members',
        ]));

        $response
            ->assertOk()
            ->assertDontSee('data-open-member-invite', false)
            ->assertDontSee('data-member-invite-modal', false)
            ->assertDontSee('data-member-menu-trigger', false);
    }

    public function test_discussions_tab_is_available_to_project_admin_and_member(): void
    {
        $fixture = $this->memberFixture();
        $thread = ProjectThread::create([
            'project_id' => $fixture['project']->id,
            'user_id' => $fixture['manager']->id,
            'title' => 'Shared planning discussion',
        ]);

        foreach ([$fixture['manager'], $fixture['existingMember']] as $user) {
            $this->actingAs($user)
                ->get(route('projects.show', [
                    'token' => $fixture['project']->token,
                    'tab' => 'discussions',
                ]))
                ->assertOk()
                ->assertSee('Project Discussions')
                ->assertSee($thread->title)
                ->assertSee('data-project-tab="discussions"', false)
                ->assertSee('aria-current="page"', false);
        }
    }

    public function test_viewer_cannot_see_or_open_project_discussions_tab(): void
    {
        $fixture = $this->memberFixture();
        ProjectThread::create([
            'project_id' => $fixture['project']->id,
            'user_id' => $fixture['manager']->id,
            'title' => 'Manager-only discussion data',
        ]);

        $this->actingAs($fixture['viewer'])
            ->get(route('projects.show', $fixture['project']->token))
            ->assertOk()
            ->assertDontSee('data-project-tab="discussions"', false)
            ->assertDontSee('Manager-only discussion data');

        $this->actingAs($fixture['viewer'])
            ->get(route('projects.show', [
                'token' => $fixture['project']->token,
                'tab' => 'discussions',
            ]))
            ->assertForbidden()
            ->assertDontSee('Manager-only discussion data');
    }

    public function test_manager_can_add_workspace_members_with_every_valid_project_role(): void
    {
        Queue::fake();
        $fixture = $this->memberFixture();

        foreach (Project::roleLabels() as $roleValue => $roleLabel) {
            $candidate = User::factory()->create();
            $fixture['workspace']->members()->attach($candidate->id, [
                'role' => Workspace::ROLE_MEMBER,
                'joined_at' => now(),
            ]);

            $this->actingAs($fixture['manager'])
                ->post(route('projects.members.store', $fixture['project']->token), [
                    'user_ids' => [$candidate->id],
                    'role' => $roleValue,
                ])
                ->assertRedirect();

            $this->assertSame(
                $roleValue,
                $fixture['project']->members()->where('users.id', $candidate->id)->firstOrFail()->pivot->role,
                "The {$roleLabel} project role was not persisted.",
            );
            $this->assertStringContainsString(
                $roleLabel,
                Notification::query()->where('user_id', $candidate->id)->latest('id')->firstOrFail()->message,
            );
        }
    }

    public function test_change_role_and_remove_member_flows_keep_existing_authorization_rules(): void
    {
        $fixture = $this->memberFixture();
        $project = $fixture['project'];
        $manager = $fixture['manager'];
        $target = $fixture['existingMember'];

        $this->actingAs($manager)
            ->patch(route('projects.members.update', [$project->token, $target]), [
                'role' => Project::ROLE_VIEWER,
            ])
            ->assertRedirect();

        $this->assertSame(
            Project::ROLE_VIEWER,
            $project->members()->where('users.id', $target->id)->firstOrFail()->pivot->role,
        );

        $this->actingAs($manager)
            ->delete(route('projects.members.destroy', [$project->token, $fixture['workspaceOwner']]))
            ->assertSessionHasErrors('member');
        $this->assertTrue($project->members()->where('users.id', $fixture['workspaceOwner']->id)->exists());

        $this->actingAs($manager)
            ->delete(route('projects.members.destroy', [$project->token, $target]))
            ->assertRedirect();
        $this->assertFalse($project->members()->where('users.id', $target->id)->exists());
    }

    public function test_non_manager_cannot_call_member_mutation_routes_directly(): void
    {
        $fixture = $this->memberFixture();
        $project = $fixture['project'];
        $member = $fixture['existingMember'];
        $candidate = $fixture['candidate'];

        $this->actingAs($member)
            ->post(route('projects.members.store', $project->token), [
                'user_ids' => [$candidate->id],
                'role' => Project::ROLE_MEMBER,
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->patch(route('projects.members.update', [$project->token, $fixture['viewer']]), [
                'role' => Project::ROLE_MEMBER,
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->delete(route('projects.members.destroy', [$project->token, $fixture['viewer']]))
            ->assertForbidden();
    }

    public function test_membership_queries_do_not_grow_with_the_number_of_members(): void
    {
        $fixture = $this->memberFixture();
        $manager = $fixture['manager'];
        $project = $fixture['project'];

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($manager)->get(route('projects.show', [
            'token' => $project->token,
            'tab' => 'members',
        ]))->assertOk();
        $initialMembershipQueries = $this->membershipQueryCount();
        DB::disableQueryLog();

        User::factory()->count(3)->create()->each(function (User $user) use ($fixture, $project): void {
            $fixture['workspace']->members()->attach($user->id, [
                'role' => Workspace::ROLE_MEMBER,
                'joined_at' => now(),
            ]);
            $project->members()->attach($user->id, [
                'role' => Project::ROLE_MEMBER,
                'joined_at' => now(),
            ]);
        });

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($manager)->get(route('projects.show', [
            'token' => $project->token,
            'tab' => 'members',
        ]))->assertOk();
        $expandedMembershipQueries = $this->membershipQueryCount();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($expandedMembershipQueries, $initialMembershipQueries);
    }

    public function test_members_tab_uses_the_new_partial_structure_without_the_inline_form(): void
    {
        $viewRoot = resource_path('views/projects/partials/show');
        $indexView = file_get_contents($viewRoot.'/members/_index.blade.php');

        foreach ([
            '_header',
            '_member-groups',
            '_member-group',
            '_member-card',
            '_invite-modal',
        ] as $partialName) {
            $this->assertFileExists($viewRoot."/members/{$partialName}.blade.php");
        }

        $this->assertStringContainsString('members._header', $indexView);
        $this->assertStringContainsString('members._member-groups', $indexView);
        $this->assertStringContainsString('members._invite-modal', $indexView);
        $this->assertFileDoesNotExist($viewRoot.'/_member-form.blade.php');
        $this->assertFileDoesNotExist($viewRoot.'/_members-tab.blade.php');
    }

    private function membershipQueryCount(): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'project_members')
                || str_contains($query['query'], 'workspace_members'))
            ->count();
    }

    /**
     * @return array{
     *     workspaceOwner: User,
     *     manager: User,
     *     existingMember: User,
     *     viewer: User,
     *     candidate: User,
     *     outsider: User,
     *     workspace: Workspace,
     *     project: Project
     * }
     */
    private function memberFixture(): array
    {
        $workspaceOwner = User::factory()->create(['name' => 'Workspace Owner User']);
        $manager = User::factory()->create(['name' => 'Current Project Admin']);
        $existingMember = User::factory()->create(['name' => 'Existing Project Member']);
        $viewer = User::factory()->create(['name' => 'Existing Project Viewer']);
        $candidate = User::factory()->create(['name' => 'Available Workspace Candidate']);
        $outsider = User::factory()->create(['name' => 'Outside Workspace User']);
        $workspace = Workspace::factory()->for($workspaceOwner, 'creator')->create();

        $workspace->members()->attach([
            $manager->id => ['role' => Workspace::ROLE_ADMIN, 'joined_at' => now()],
            $existingMember->id => ['role' => Workspace::ROLE_MEMBER, 'joined_at' => now()],
            $viewer->id => ['role' => Workspace::ROLE_MEMBER, 'joined_at' => now()],
            $candidate->id => ['role' => Workspace::ROLE_MEMBER, 'joined_at' => now()],
        ]);

        $project = Project::factory()
            ->for($workspace)
            ->for($workspaceOwner, 'creator')
            ->create(['name' => 'Member UX Project']);
        $project->members()->attach([
            $workspaceOwner->id => ['role' => Project::ROLE_MANAGER, 'joined_at' => now()],
            $manager->id => ['role' => Project::ROLE_MANAGER, 'joined_at' => now()],
            $existingMember->id => ['role' => Project::ROLE_MEMBER, 'joined_at' => now()],
            $viewer->id => ['role' => Project::ROLE_VIEWER, 'joined_at' => now()],
        ]);

        return compact(
            'workspaceOwner',
            'manager',
            'existingMember',
            'viewer',
            'candidate',
            'outsider',
            'workspace',
            'project',
        );
    }
}
