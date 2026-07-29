<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChatMessage;
use App\Models\WorkspaceChatRead;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class WorkspaceChatTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProjectTemplateTestSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('profile_photo')->nullable();
            $table->string('avatar_url')->nullable();
        });
        (require database_path('migrations/2026_07_28_144655_create_workspace_chat_messages_table.php'))->up();
        (require database_path('migrations/2026_07_28_144655_create_workspace_chat_reads_table.php'))->up();
        (require database_path('migrations/2026_07_28_150913_create_workspace_chat_message_mentions_table.php'))->up();
        (require database_path('migrations/2026_07_28_150913_add_workspace_chat_context_to_notifications_table.php'))->up();

        RateLimiter::clear('workspace-chat-poll');
        RateLimiter::clear('workspace-chat-write');
        RateLimiter::clear('workspace-chat-mentions');
    }

    public function test_workspace_relations_and_read_state_unique_constraint_are_configured(): void
    {
        $fixture = $this->workspaceFixture();
        $message = WorkspaceChatMessage::factory()
            ->for($fixture['workspace'])
            ->for($fixture['member'])
            ->create();
        $read = WorkspaceChatRead::factory()
            ->for($fixture['workspace'])
            ->for($fixture['member'])
            ->create(['last_read_message_id' => $message->id]);

        $this->assertTrue($fixture['workspace']->chatMessages->contains($message));
        $this->assertTrue($fixture['workspace']->chatReads->contains($read));
        $this->assertTrue($read->lastReadMessage->is($message));

        $this->expectException(QueryException::class);
        WorkspaceChatRead::factory()
            ->for($fixture['workspace'])
            ->for($fixture['member'])
            ->create();
    }

    public function test_owner_admin_member_and_super_admin_can_open_chat_while_outsider_is_forbidden(): void
    {
        $fixture = $this->workspaceFixture();
        $outsider = User::factory()->create();
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $superAdmin->assignRole('super_admin');

        foreach ([
            $fixture['owner'],
            $fixture['admin'],
            $fixture['member'],
            $superAdmin,
        ] as $actor) {
            $this->actingAs($actor)
                ->getJson(route('workspace-chat.messages.index', $fixture['workspace']))
                ->assertOk()
                ->assertJsonStructure(['messages', 'has_more']);
        }

        $this->actingAs($outsider)
            ->getJson(route('workspace-chat.messages.index', $fixture['workspace']))
            ->assertForbidden();

        $this->actingAs($outsider)
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => 'Forbidden message',
            ])
            ->assertForbidden();
    }

    public function test_valid_content_is_trimmed_scoped_and_returned_without_sensitive_user_data(): void
    {
        $fixture = $this->workspaceFixture();
        $notificationCount = Notification::count();

        $this->actingAs($fixture['member'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => '  Safe workspace message  ',
            ])
            ->assertCreated()
            ->assertJsonPath('content', 'Safe workspace message')
            ->assertJsonPath('sender.id', $fixture['member']->id)
            ->assertJsonPath('sender.name', $fixture['member']->name)
            ->assertJsonPath('can_edit', true)
            ->assertJsonPath('can_delete', true)
            ->assertJsonMissingPath('sender.email')
            ->assertJsonMissingPath('sender.password')
            ->assertJsonMissingPath('user');

        $this->assertDatabaseHas('workspace_chat_messages', [
            'workspace_id' => $fixture['workspace']->id,
            'user_id' => $fixture['member']->id,
            'content' => 'Safe workspace message',
        ]);
        $this->assertSame($notificationCount, Notification::count());
    }

    public function test_empty_and_overlong_content_are_rejected_without_creating_messages(): void
    {
        $fixture = $this->workspaceFixture();

        foreach (['', " \n\t "] as $content) {
            $this->actingAs($fixture['member'])
                ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                    'content' => $content,
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('content');
        }

        $this->actingAs($fixture['member'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => str_repeat('a', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->assertDatabaseCount('workspace_chat_messages', 0);
    }

    public function test_sender_can_edit_and_hard_delete_own_message_with_edited_timestamp(): void
    {
        $fixture = $this->workspaceFixture();
        $message = $this->message($fixture['workspace'], $fixture['member'], 'Original');

        $this->actingAs($fixture['member'])
            ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                'content' => ' Updated ',
            ])
            ->assertOk()
            ->assertJsonPath('content', 'Updated')
            ->assertJsonPath('can_edit', true)
            ->assertJsonPath('can_delete', true);

        $this->assertNotNull($message->fresh()->edited_at);

        $this->actingAs($fixture['member'])
            ->deleteJson(route('workspace-chat.messages.destroy', [$fixture['workspace'], $message]))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('workspace_chat_messages', ['id' => $message->id]);
    }

    public function test_other_member_owner_and_admin_cannot_edit_or_delete_another_senders_message(): void
    {
        $fixture = $this->workspaceFixture();
        $message = $this->message($fixture['workspace'], $fixture['member'], 'Sender only');

        foreach ([$fixture['owner'], $fixture['admin']] as $actor) {
            $this->actingAs($actor)
                ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                    'content' => 'Unauthorized edit',
                ])
                ->assertForbidden();

            $this->actingAs($actor)
                ->deleteJson(route('workspace-chat.messages.destroy', [$fixture['workspace'], $message]))
                ->assertForbidden();
        }

        $this->assertSame('Sender only', $message->fresh()->content);
    }

    public function test_cross_workspace_message_mismatch_returns_not_found_before_authorization(): void
    {
        $fixture = $this->workspaceFixture();
        $otherOwner = User::factory()->create();
        $otherWorkspace = Workspace::factory()->for($otherOwner, 'creator')->create();
        $otherWorkspace->members()->attach($otherOwner, [
            'role' => Workspace::ROLE_OWNER,
            'joined_at' => now(),
        ]);
        $message = $this->message($otherWorkspace, $otherOwner, 'Other workspace');

        $this->actingAs($otherOwner)
            ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                'content' => 'Cross-workspace edit',
            ])
            ->assertNotFound();

        $this->actingAs($otherOwner)
            ->deleteJson(route('workspace-chat.messages.destroy', [$fixture['workspace'], $message]))
            ->assertNotFound();

        $this->assertSame('Other workspace', $message->fresh()->content);
    }

    public function test_initial_before_and_after_pagination_are_limited_scoped_and_stably_ordered(): void
    {
        $fixture = $this->workspaceFixture();
        $otherWorkspace = Workspace::factory()->for($fixture['owner'], 'creator')->create();

        foreach (range(1, 35) as $number) {
            $this->message($fixture['workspace'], $fixture['member'], "Message {$number}");
        }
        $otherMessage = $this->message($otherWorkspace, $fixture['owner'], 'Other workspace');

        $initial = $this->actingAs($fixture['member'])
            ->getJson(route('workspace-chat.messages.index', $fixture['workspace']))
            ->assertOk()
            ->assertJsonCount(30, 'messages')
            ->assertJsonPath('has_more', true)
            ->json();

        $initialIds = collect($initial['messages'])->pluck('id');
        $this->assertSame($initialIds->sort()->values()->all(), $initialIds->all());
        $this->assertNotContains($otherMessage->id, $initialIds->all());

        $older = $this->actingAs($fixture['member'])
            ->getJson(route('workspace-chat.messages.index', [
                'workspace' => $fixture['workspace'],
                'before_id' => $initialIds->first(),
            ]))
            ->assertOk()
            ->assertJsonCount(5, 'messages')
            ->assertJsonPath('has_more', false)
            ->json('messages');
        $this->assertTrue(collect($older)->every(
            fn (array $message): bool => $message['id'] < $initialIds->first(),
        ));

        $newMessage = $this->message($fixture['workspace'], $fixture['owner'], 'Newest');
        $after = $this->actingAs($fixture['member'])
            ->getJson(route('workspace-chat.messages.index', [
                'workspace' => $fixture['workspace'],
                'after_id' => $initialIds->last(),
            ]))
            ->assertOk()
            ->json('messages');
        $this->assertSame([$newMessage->id], collect($after)->pluck('id')->all());
    }

    public function test_message_index_eager_loads_senders_without_n_plus_one_queries(): void
    {
        $fixture = $this->workspaceFixture();
        $senders = User::factory()->count(10)->create();

        foreach ($senders as $sender) {
            $this->message($fixture['workspace'], $sender, "Message by {$sender->name}");
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($fixture['member'])
            ->getJson(route('workspace-chat.messages.index', $fixture['workspace']))
            ->assertOk();
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $senderQueries = collect($queries)->filter(
            fn (array $query): bool => str_contains($query['query'], 'from "users"')
                && str_contains($query['query'], '"users"."id" in'),
        );

        $this->assertCount(1, $senderQueries);
    }

    public function test_opening_chat_and_read_endpoint_update_workspace_specific_read_state(): void
    {
        $fixture = $this->workspaceFixture();
        $otherWorkspace = Workspace::factory()->for($fixture['owner'], 'creator')->create();
        $ownMessage = $this->message($fixture['workspace'], $fixture['member'], 'Own message');

        $this->actingAs($fixture['member'])
            ->get(route('workspaces.show', $fixture['workspace']->token))
            ->assertOk()
            ->assertViewHas('chatUnreadCount', 0);

        $otherMessage = $this->message($fixture['workspace'], $fixture['owner'], 'Other message');
        $this->actingAs($fixture['member'])
            ->get(route('workspaces.show', $fixture['workspace']->token))
            ->assertOk()
            ->assertViewHas('chatUnreadCount', 1);

        $this->actingAs($fixture['member'])
            ->get(route('workspaces.show', [
                'token' => $fixture['workspace']->token,
                'tab' => 'chat',
            ]))
            ->assertOk();

        $this->assertDatabaseHas('workspace_chat_reads', [
            'workspace_id' => $fixture['workspace']->id,
            'user_id' => $fixture['member']->id,
            'last_read_message_id' => $otherMessage->id,
        ]);
        $this->assertDatabaseMissing('workspace_chat_reads', [
            'workspace_id' => $otherWorkspace->id,
            'user_id' => $fixture['member']->id,
        ]);

        $this->actingAs($fixture['member'])
            ->postJson(route('workspace-chat.read', $fixture['workspace']), [
                'message_id' => $ownMessage->id,
            ])
            ->assertOk()
            ->assertJsonPath('last_read_message_id', $otherMessage->id)
            ->assertJsonPath('unread_count', 0);
    }

    public function test_deleting_a_last_read_message_preserves_a_safe_previous_cursor(): void
    {
        $fixture = $this->workspaceFixture();
        $older = $this->message($fixture['workspace'], $fixture['member'], 'Older');
        $latest = $this->message($fixture['workspace'], $fixture['member'], 'Latest');
        WorkspaceChatRead::factory()
            ->for($fixture['workspace'])
            ->for($fixture['member'])
            ->create(['last_read_message_id' => $latest->id]);

        $this->actingAs($fixture['member'])
            ->deleteJson(route('workspace-chat.messages.destroy', [$fixture['workspace'], $latest]))
            ->assertOk();

        $this->assertDatabaseHas('workspace_chat_reads', [
            'workspace_id' => $fixture['workspace']->id,
            'user_id' => $fixture['member']->id,
            'last_read_message_id' => $older->id,
        ]);
    }

    public function test_workspace_chat_tab_renders_partials_active_state_initial_limit_and_escaped_content(): void
    {
        $fixture = $this->workspaceFixture();

        foreach (range(1, 31) as $number) {
            $content = $number === 31
                ? '<script>alert("unsafe")</script>'
                : "Message {$number}";
            $this->message($fixture['workspace'], $fixture['member'], $content);
        }

        $response = $this->actingAs($fixture['member'])
            ->get(route('workspaces.show', [
                'token' => $fixture['workspace']->token,
                'tab' => 'chat',
            ]))
            ->assertOk()
            ->assertSee('Workspace Chat')
            ->assertSee('aria-current="page"', false)
            ->assertSee('tab=chat', false)
            ->assertSee('&lt;script&gt;alert(&quot;unsafe&quot;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert("unsafe")</script>', false)
            ->assertDontSee('attachment', false);

        $this->assertCount(30, $response->viewData('chatMessages'));
        $this->assertTrue($response->viewData('chatHasMore'));

        foreach ([
            '_index.blade.php',
            '_chat-panel.blade.php',
            '_message-list.blade.php',
            '_message-item.blade.php',
            '_composer.blade.php',
            '_edit-message-modal.blade.php',
            '_empty-state.blade.php',
            '_mention-suggestions.blade.php',
            '_message-highlight.blade.php',
        ] as $partial) {
            $this->assertFileExists(
                resource_path('views/workspaces/partials/show/chat/'.$partial),
            );
        }

        $javascript = file_get_contents(resource_path('js/workspace-chat.js'));
        $this->assertStringContainsString('textContent', $javascript);
        $this->assertStringContainsString('AbortController', $javascript);
        $this->assertStringContainsString('document.hidden', $javascript);
        $this->assertStringNotContainsString('innerHTML', $javascript);
        $this->assertStringNotContainsString('attachment', strtolower($javascript));
    }

    public function test_routes_use_auth_and_expected_rate_limiters(): void
    {
        $routes = app('router')->getRoutes();
        $indexMiddleware = $routes->getByName('workspace-chat.messages.index')->gatherMiddleware();
        $storeMiddleware = $routes->getByName('workspace-chat.messages.store')->gatherMiddleware();
        $updateMiddleware = $routes->getByName('workspace-chat.messages.update')->gatherMiddleware();
        $destroyMiddleware = $routes->getByName('workspace-chat.messages.destroy')->gatherMiddleware();
        $mentionMiddleware = $routes->getByName('workspace-chat.mentions')->gatherMiddleware();

        $this->assertContains('auth', $indexMiddleware);
        $this->assertContains('throttle:workspace-chat-poll', $indexMiddleware);
        $this->assertContains('throttle:workspace-chat-write', $storeMiddleware);
        $this->assertContains('throttle:workspace-chat-write', $updateMiddleware);
        $this->assertContains('throttle:workspace-chat-write', $destroyMiddleware);
        $this->assertContains('auth', $mentionMiddleware);
        $this->assertContains('throttle:workspace-chat-mentions', $mentionMiddleware);
    }

    public function test_user_deletion_keeps_chat_history_and_nulls_sender(): void
    {
        $fixture = $this->workspaceFixture();
        $message = $this->message($fixture['workspace'], $fixture['member'], 'Preserved history');

        $fixture['workspace']->members()->detach($fixture['member']);
        $fixture['member']->delete();

        $this->assertDatabaseHas('workspace_chat_messages', [
            'id' => $message->id,
            'workspace_id' => $fixture['workspace']->id,
            'user_id' => null,
            'content' => 'Preserved history',
        ]);
    }

    /**
     * @return array{owner: User, admin: User, member: User, workspace: Workspace}
     */
    private function workspaceFixture(): array
    {
        $owner = User::factory()->create(['name' => 'Workspace Owner']);
        $admin = User::factory()->create(['name' => 'Workspace Admin']);
        $member = User::factory()->create(['name' => 'Workspace Member']);
        $workspace = Workspace::factory()->for($owner, 'creator')->create();

        $workspace->members()->attach([
            $owner->id => [
                'role' => Workspace::ROLE_OWNER,
                'joined_at' => now(),
            ],
            $admin->id => [
                'role' => Workspace::ROLE_ADMIN,
                'joined_at' => now(),
            ],
            $member->id => [
                'role' => Workspace::ROLE_MEMBER,
                'joined_at' => now(),
            ],
        ]);

        return compact('owner', 'admin', 'member', 'workspace');
    }

    private function message(
        Workspace $workspace,
        User $sender,
        string $content,
    ): WorkspaceChatMessage {
        return WorkspaceChatMessage::factory()
            ->for($workspace)
            ->for($sender)
            ->create(['content' => $content]);
    }
}
