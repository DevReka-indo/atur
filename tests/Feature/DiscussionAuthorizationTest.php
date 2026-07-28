<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\ThreadUserRead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiscussionAuthorizationTest extends TestCase
{
    private User $projectAManager;

    private User $projectAMember;

    private User $projectBMember;

    private User $projectAViewer;

    private User $outsider;

    private Project $projectA;

    private Project $projectB;

    private ProjectThread $threadA;

    private ProjectThread $threadB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createTestSchema();
        $this->createFixtures();
    }

    public function test_project_member_cannot_open_another_projects_thread_through_mismatched_context(): void
    {
        $this->actingAs($this->projectAMember)
            ->get(route('discussion.chat', [$this->projectA, $this->threadB]))
            ->assertNotFound();
    }

    public function test_project_member_cannot_send_a_message_to_another_projects_thread(): void
    {
        $messageCount = ProjectThreadMessage::count();

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadB]), [
                'content' => 'Cross-project message',
            ])
            ->assertNotFound();

        $this->assertSame($messageCount, ProjectThreadMessage::count());
    }

    public function test_project_member_cannot_rename_or_delete_another_projects_thread(): void
    {
        $this->actingAs($this->projectAMember)
            ->patch(route('discussion.threads.update', [$this->projectA, $this->threadB]), [
                'name' => 'Renamed across projects',
            ])
            ->assertNotFound();

        $this->actingAs($this->projectAMember)
            ->delete(route('discussion.threads.destroy', [$this->projectA, $this->threadB]))
            ->assertNotFound();

        $this->assertSame('Project B Thread', $this->threadB->fresh()->title);
    }

    public function test_message_sender_cannot_update_or_delete_through_a_mismatched_project_and_thread_context(): void
    {
        $message = $this->createMessage($this->threadB, $this->projectBMember, 'Project B message');

        $this->actingAs($this->projectBMember)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => 'Cross-project update',
            ])
            ->assertNotFound();

        $this->actingAs($this->projectBMember)
            ->deleteJson(route('messages.destroy', [$this->projectA, $this->threadA, $message]))
            ->assertNotFound();

        $this->assertSame('Project B message', $message->fresh()->content);
    }

    public function test_non_member_cannot_read_a_valid_project_thread(): void
    {
        $this->actingAs($this->outsider)
            ->get(route('discussion.chat', [$this->projectA, $this->threadA]))
            ->assertForbidden();
    }

    public function test_valid_project_member_can_read_and_send_messages(): void
    {
        $this->actingAs($this->projectAMember)
            ->get(route('discussion.chat', [$this->projectA, $this->threadA]))
            ->assertOk();

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => 'Valid project message',
            ])
            ->assertOk()
            ->assertJsonPath('content', 'Valid project message');

        $this->assertTrue(
            $this->threadA->messages()
                ->where('user_id', $this->projectAMember->id)
                ->where('content', 'Valid project message')
                ->exists(),
        );
    }

    public function test_project_admin_can_create_rename_and_delete_discussion_threads(): void
    {
        $this->actingAs($this->projectAManager)
            ->post(route('discussion.threads.store', $this->projectA), [
                'name' => 'Admin Discussion',
            ])
            ->assertRedirect(route('discussion.show', $this->projectA));

        $thread = ProjectThread::query()->where('title', 'Admin Discussion')->firstOrFail();

        $this->actingAs($this->projectAManager)
            ->postJson(route('discussion.messages.store', [$this->projectA, $thread]), [
                'content' => 'Project Admin message',
            ])
            ->assertOk()
            ->assertJsonPath('content', 'Project Admin message');

        $this->actingAs($this->projectAManager)
            ->patch(route('discussion.threads.update', [$this->projectA, $thread]), [
                'name' => 'Renamed Discussion',
            ])
            ->assertRedirect(route('discussion.show', $this->projectA));

        $this->assertSame('Renamed Discussion', $thread->fresh()->title);

        $this->actingAs($this->projectAManager)
            ->delete(route('discussion.threads.destroy', [$this->projectA, $thread]))
            ->assertRedirect(route('discussion.show', $this->projectA));

        $this->assertModelMissing($thread);
    }

    public function test_member_cannot_create_or_manage_threads_even_when_they_created_the_thread(): void
    {
        $this->actingAs($this->projectAMember)
            ->post(route('discussion.threads.store', $this->projectA), [
                'name' => 'Forbidden Member Discussion',
            ])
            ->assertForbidden();

        $this->actingAs($this->projectAMember)
            ->patch(route('discussion.threads.update', [$this->projectA, $this->threadA]), [
                'name' => 'Member Rename Attempt',
            ])
            ->assertForbidden();

        $this->actingAs($this->projectAMember)
            ->delete(route('discussion.threads.destroy', [$this->projectA, $this->threadA]))
            ->assertForbidden();

        $this->assertSame('Project A Thread', $this->threadA->fresh()->title);
    }

    public function test_viewer_cannot_read_post_manage_or_receive_project_discussion_unread(): void
    {
        $this->createMessage($this->threadA, $this->projectAMember, 'Unread for contributors only');

        $this->actingAs($this->projectAViewer)
            ->get(route('discussion.show', $this->projectA))
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->get(route('discussion.chat', [$this->projectA, $this->threadA]))
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => 'Viewer message',
            ])
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->post(route('discussion.threads.store', $this->projectA), [
                'name' => 'Viewer Discussion',
            ])
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->patch(route('discussion.threads.update', [$this->projectA, $this->threadA]), [
                'name' => 'Viewer Rename',
            ])
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->delete(route('discussion.threads.destroy', [$this->projectA, $this->threadA]))
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->getJson(route('discussion.unread', $this->projectA))
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->getJson(route('discussion.unread-sidebar'))
            ->assertOk()
            ->assertJsonPath('count', 0);

        $this->assertFalse(
            ThreadUserRead::query()->where('user_id', $this->projectAViewer->id)->exists(),
        );
    }

    public function test_hub_only_lists_projects_where_user_is_admin_or_member(): void
    {
        $this->actingAs($this->projectAMember)
            ->get(route('discussion.index'))
            ->assertOk()
            ->assertSee('Project A')
            ->assertDontSee('Project B');

        $this->actingAs($this->projectAViewer)
            ->get(route('discussion.index'))
            ->assertOk()
            ->assertDontSee('Project A Thread')
            ->assertViewHas('projects', fn ($projects): bool => $projects->isEmpty());
    }

    public function test_thread_management_button_is_only_rendered_for_project_admin(): void
    {
        $this->actingAs($this->projectAManager)
            ->get(route('discussion.show', $this->projectA))
            ->assertOk()
            ->assertSee('New Discussion')
            ->assertSee('data-discussion-rename', false);

        $this->actingAs($this->projectAMember)
            ->get(route('discussion.show', $this->projectA))
            ->assertOk()
            ->assertDontSee('New Discussion')
            ->assertDontSee('data-discussion-rename', false);
    }

    public function test_hub_search_and_unread_filters_preserve_access_scope(): void
    {
        $this->createMessage($this->threadA, $this->projectBMember, 'Unread planning update');

        $this->actingAs($this->projectAMember)
            ->get(route('discussion.index', [
                'search' => 'Project A Thread',
                'unread' => 1,
            ]))
            ->assertOk()
            ->assertSee('Project A')
            ->assertSee('1 unread')
            ->assertDontSee('Project B Thread');

        $this->actingAs($this->projectAMember)
            ->get(route('discussion.index', ['search' => 'Project B']))
            ->assertOk()
            ->assertViewHas('projects', fn ($projects): bool => $projects->isEmpty());
    }

    public function test_super_admin_can_use_the_existing_discussion_authorization_shortcut(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)
            ->get(route('discussion.show', $this->projectA))
            ->assertOk()
            ->assertSee('New Discussion');

        $this->actingAs($superAdmin)
            ->get(route('discussion.index'))
            ->assertOk()
            ->assertSee('Project A')
            ->assertSee('Project B');
    }

    public function test_hub_discussion_queries_do_not_grow_with_the_number_of_threads(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->projectAMember)
            ->get(route('discussion.index'))
            ->assertOk();
        $initialQueryCount = $this->discussionQueryCount();
        DB::disableQueryLog();

        foreach (range(1, 3) as $index) {
            $thread = ProjectThread::create([
                'project_id' => $this->projectA->id,
                'user_id' => $this->projectAManager->id,
                'title' => "Additional Discussion {$index}",
            ]);
            $this->createMessage($thread, $this->projectAManager, "Message {$index}");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->projectAMember)
            ->get(route('discussion.index'))
            ->assertOk();
        $expandedQueryCount = $this->discussionQueryCount();
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($initialQueryCount, $expandedQueryCount);
    }

    public function test_valid_sender_can_update_and_delete_their_own_message(): void
    {
        $message = $this->createMessage($this->threadA, $this->projectAMember, 'Original message');

        $this->actingAs($this->projectAMember)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => 'Updated message',
            ])
            ->assertOk()
            ->assertJsonPath('content', 'Updated message');

        $this->assertSame('Updated message', $message->fresh()->content);

        $this->actingAs($this->projectAMember)
            ->deleteJson(route('messages.destroy', [$this->projectA, $this->threadA, $message]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertNull($message->fresh());
    }

    public function test_other_project_member_cannot_update_or_delete_message_they_did_not_send(): void
    {
        $message = $this->createMessage($this->threadA, $this->projectAMember, 'Sender-only message');

        $this->actingAs($this->projectBMember)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => 'Changed by another member',
            ])
            ->assertForbidden();

        $this->actingAs($this->projectBMember)
            ->deleteJson(route('messages.destroy', [$this->projectA, $this->threadA, $message]))
            ->assertForbidden();

        $this->assertSame('Sender-only message', $message->fresh()->content);
    }

    public function test_valid_resource_with_unauthorized_user_returns_forbidden(): void
    {
        $message = $this->createMessage($this->threadA, $this->projectAMember, 'Protected message');

        $this->actingAs($this->outsider)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => 'Unauthorized message',
            ])
            ->assertForbidden();

        $this->actingAs($this->outsider)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => 'Unauthorized update',
            ])
            ->assertForbidden();

        $this->assertSame('Protected message', $message->fresh()->content);
    }

    private function createFixtures(): void
    {
        $this->projectAManager = User::factory()->create();
        $this->projectAMember = User::factory()->create();
        $this->projectBMember = User::factory()->create();
        $this->projectAViewer = User::factory()->create();
        $this->outsider = User::factory()->create();

        $workspaceA = Workspace::factory()->for($this->projectAMember, 'creator')->create();
        $workspaceB = Workspace::factory()->for($this->projectBMember, 'creator')->create();

        $this->projectA = Project::factory()
            ->for($workspaceA)
            ->for($this->projectAMember, 'creator')
            ->create(['name' => 'Project A']);
        $this->projectB = Project::factory()
            ->for($workspaceB)
            ->for($this->projectBMember, 'creator')
            ->create(['name' => 'Project B']);

        $this->projectA->members()->attach($this->projectAMember->id, [
            'role' => Project::ROLE_MEMBER,
            'joined_at' => now(),
        ]);
        $this->projectA->members()->attach($this->projectAManager->id, [
            'role' => Project::ROLE_MANAGER,
            'joined_at' => now(),
        ]);
        $this->projectA->members()->attach($this->projectAViewer->id, [
            'role' => Project::ROLE_VIEWER,
            'joined_at' => now(),
        ]);
        $this->projectA->members()->attach($this->projectBMember->id, [
            'role' => Project::ROLE_MEMBER,
            'joined_at' => now(),
        ]);
        $this->projectB->members()->attach($this->projectBMember->id, [
            'role' => Project::ROLE_MEMBER,
            'joined_at' => now(),
        ]);

        $this->threadA = ProjectThread::create([
            'project_id' => $this->projectA->id,
            'user_id' => $this->projectAMember->id,
            'title' => 'Project A Thread',
        ]);
        $this->threadB = ProjectThread::create([
            'project_id' => $this->projectB->id,
            'user_id' => $this->projectBMember->id,
            'title' => 'Project B Thread',
        ]);
    }

    private function createMessage(ProjectThread $thread, User $sender, string $content): ProjectThreadMessage
    {
        return ProjectThreadMessage::create([
            'project_thread_id' => $thread->id,
            'user_id' => $sender->id,
            'content' => $content,
        ]);
    }

    private function discussionQueryCount(): int
    {
        return collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains($query['query'], 'project_threads')
                || str_contains($query['query'], 'project_thread_messages')
                || str_contains($query['query'], 'thread_user_reads'))
            ->count();
    }

    private function createTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->boolean('has_password')->default(true);
            $table->string('role')->default('member');
            $table->boolean('is_active')->default(true);
            $table->string('profile_photo')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        $permissionMigration = require database_path('migrations/2026_07_22_083512_create_permission_tables.php');
        $permissionMigration->up();

        Schema::create('workspaces', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('created_by');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('workspace_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id');
            $table->foreignId('user_id');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['workspace_id', 'user_id']);
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workspace_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('status')->default('active');
            $table->foreignId('created_by');
            $table->string('token', 32)->nullable()->unique();
            $table->timestamps();
        });

        Schema::create('project_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('user_id');
            $table->string('role')->default('member');
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
        });

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
            $table->timestamps();
        });

        Schema::create('thread_user_reads', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('thread_id');
            $table->foreignId('user_id');
            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();
            $table->unique(['thread_id', 'user_id']);
        });

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->foreignId('task_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('action');
            $table->string('entity_type');
            $table->foreignId('entity_id');
            $table->text('description')->nullable();
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->timestamps();
        });
    }
}
