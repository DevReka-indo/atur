<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DiscussionAuthorizationTest extends TestCase
{
    private User $projectAMember;

    private User $projectBMember;

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
        $this->projectAMember = User::factory()->create();
        $this->projectBMember = User::factory()->create();
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
