<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChatMessage;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class WorkspaceChatMentionNotificationTest extends TestCase
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
        Queue::fake();
    }

    public function test_mention_autocomplete_only_returns_other_workspace_members_with_safe_fields(): void
    {
        $fixture = $this->workspaceFixture();
        $outsider = User::factory()->create([
            'name' => 'Outside Person',
            'email' => 'outside.secret@example.test',
        ]);

        $this->actingAs($fixture['owner'])
            ->getJson(route('workspace-chat.mentions', [
                'workspace' => $fixture['workspace'],
                'search' => 'Workspace Member',
            ]))
            ->assertOk()
            ->assertJsonPath('members.0.id', $fixture['member']->id)
            ->assertJsonPath('members.0.name', $fixture['member']->name)
            ->assertJsonMissingPath('members.0.email')
            ->assertJsonMissingPath('members.0.password')
            ->assertJsonMissingPath('members.0.remember_token');

        $this->actingAs($fixture['owner'])
            ->getJson(route('workspace-chat.mentions', [
                'workspace' => $fixture['workspace'],
                'search' => $outsider->email,
            ]))
            ->assertOk()
            ->assertJsonCount(0, 'members');

        $selfResults = $this->actingAs($fixture['member'])
            ->getJson(route('workspace-chat.mentions', [
                'workspace' => $fixture['workspace'],
                'search' => $fixture['member']->email,
            ]))
            ->assertOk()
            ->json('members');
        $this->assertNotContains($fixture['member']->id, collect($selfResults)->pluck('id'));

        $this->actingAs($outsider)
            ->getJson(route('workspace-chat.mentions', $fixture['workspace']))
            ->assertForbidden();

        $javascript = file_get_contents(resource_path('js/workspace-chat.js'));
        foreach (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'] as $key) {
            $this->assertStringContainsString($key, $javascript);
        }
        $this->assertStringContainsString('textContent', $javascript);
        $this->assertStringNotContainsString('innerHTML', $javascript);
        $suggestions = file_get_contents(resource_path(
            'views/workspaces/partials/show/chat/_mention-suggestions.blade.php',
        ));
        $this->assertStringContainsString('role="listbox"', $suggestions);
    }

    public function test_store_creates_unique_mention_relations_and_notifies_only_mentioned_targets(): void
    {
        $fixture = $this->workspaceFixture();
        $content = sprintf(
            'Halo @[%s](user:%d), lagi @[%s](user:%d), dari @[%s](user:%d)',
            $fixture['member']->name,
            $fixture['member']->id,
            $fixture['member']->name,
            $fixture['member']->id,
            $fixture['owner']->name,
            $fixture['owner']->id,
        );

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => $content,
            ])
            ->assertCreated()
            ->assertJsonPath('content_segments.1.type', 'mention')
            ->assertJsonPath('content_segments.1.user_id', $fixture['member']->id);

        $message = WorkspaceChatMessage::query()->sole();
        $this->assertEqualsCanonicalizing(
            [$fixture['member']->id, $fixture['owner']->id],
            $message->mentions()->pluck('users.id')->all(),
        );
        $this->assertSame(
            1,
            Notification::query()
                ->where('type', Notification::TYPE_WORKSPACE_CHAT_MENTION)
                ->where('user_id', $fixture['member']->id)
                ->count(),
        );
        $this->assertFalse(
            Notification::query()
                ->where('user_id', $fixture['owner']->id)
                ->exists(),
        );
        Queue::assertNothingPushed();
    }

    public function test_invalid_or_outsider_markers_are_plain_text_and_create_no_mentions(): void
    {
        $fixture = $this->workspaceFixture();
        $outsider = User::factory()->create(['name' => 'Outside Person']);

        $response = $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => "Halo @[Outside Alias](user:{$outsider->id}) dan @[broken](user:nope)",
            ])
            ->assertCreated()
            ->assertJsonPath('plain_text', 'Halo @Outside Alias dan @[broken](user:nope)');

        $message = WorkspaceChatMessage::query()->sole();
        $this->assertSame(
            'Halo @Outside Alias dan @[broken](user:nope)',
            $message->content,
        );
        $this->assertDatabaseCount('workspace_chat_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
        $this->assertStringNotContainsString(
            (string) $outsider->id,
            $response->json('plain_text'),
        );
    }

    public function test_content_without_mentions_still_works_without_notification(): void
    {
        $fixture = $this->workspaceFixture();

        $this->actingAs($fixture['member'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => 'Pesan biasa tanpa mention.',
            ])
            ->assertCreated()
            ->assertJsonPath('plain_text', 'Pesan biasa tanpa mention.');

        $this->assertDatabaseCount('workspace_chat_messages', 1);
        $this->assertDatabaseCount('workspace_chat_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_more_than_ten_unique_mentions_is_rejected_atomically(): void
    {
        $fixture = $this->workspaceFixture();
        $members = User::factory()->count(11)->create();
        $fixture['workspace']->members()->attach(
            $members->mapWithKeys(fn (User $user): array => [
                $user->id => [
                    'role' => Workspace::ROLE_MEMBER,
                    'joined_at' => now(),
                ],
            ])->all(),
        );
        $content = $members
            ->map(fn (User $user): string => "@[{$user->name}](user:{$user->id})")
            ->implode(' ');

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => $content,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->assertDatabaseCount('workspace_chat_messages', 0);
        $this->assertDatabaseCount('workspace_chat_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_update_syncs_mentions_and_never_renotifies_an_existing_message_target(): void
    {
        $fixture = $this->workspaceFixture();
        $firstMarker = $this->marker($fixture['member']);
        $secondMarker = $this->marker($fixture['admin']);

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => "Pertama {$firstMarker}",
            ])
            ->assertCreated();
        $message = WorkspaceChatMessage::query()->sole();

        $this->actingAs($fixture['owner'])
            ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                'content' => "Keduanya {$firstMarker} {$secondMarker}",
            ])
            ->assertOk();
        $this->assertDatabaseCount('notifications', 2);
        $this->assertEqualsCanonicalizing(
            [$fixture['member']->id, $fixture['admin']->id],
            $message->mentions()->pluck('users.id')->all(),
        );

        $this->actingAs($fixture['owner'])
            ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                'content' => "Tetap sama {$firstMarker} {$secondMarker}",
            ])
            ->assertOk();
        $this->assertDatabaseCount('notifications', 2);

        $this->actingAs($fixture['owner'])
            ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                'content' => "Hanya kedua {$secondMarker}",
            ])
            ->assertOk();
        $this->assertSame(
            [$fixture['admin']->id],
            $message->mentions()->pluck('users.id')->all(),
        );

        $this->actingAs($fixture['owner'])
            ->patchJson(route('workspace-chat.messages.update', [$fixture['workspace'], $message]), [
                'content' => "Tambah kembali {$firstMarker} {$secondMarker}",
            ])
            ->assertOk();
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_delete_cascades_mention_relations_but_keeps_notification_target_safe(): void
    {
        $fixture = $this->workspaceFixture();

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => 'Periksa '.$this->marker($fixture['member']),
            ])
            ->assertCreated();
        $message = WorkspaceChatMessage::query()->sole();
        $notification = Notification::query()->sole();
        $url = $notification->url;

        $this->actingAs($fixture['owner'])
            ->deleteJson(route('workspace-chat.messages.destroy', [$fixture['workspace'], $message]))
            ->assertOk();

        $this->assertDatabaseCount('workspace_chat_message_mentions', 0);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'workspace_chat_message_id' => null,
            'url' => $url,
        ]);

        $this->actingAs($fixture['member'])
            ->get(route('notifications.open', $notification))
            ->assertRedirect($url);
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($fixture['member'])
            ->get($url)
            ->assertOk()
            ->assertSee('Message yang disebut pada notification sudah tidak tersedia.');
    }

    public function test_notification_has_safe_limited_excerpt_url_and_structured_metadata(): void
    {
        $fixture = $this->workspaceFixture();
        $content = $this->marker($fixture['member'])
            .' <script>alert("unsafe")</script> '
            .str_repeat('panjang ', 40);

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => $content,
            ])
            ->assertCreated();

        $message = WorkspaceChatMessage::query()->sole();
        $notification = Notification::query()->sole();
        $expectedUrl = route('workspaces.show', [
            'token' => $fixture['workspace']->token,
            'tab' => 'chat',
            'message' => $message->id,
        ], false);

        $this->assertSame($expectedUrl, $notification->url);
        $this->assertSame($fixture['workspace']->id, $notification->metadata['workspace_id']);
        $this->assertSame($message->id, $notification->metadata['message_id']);
        $this->assertSame($fixture['owner']->id, $notification->metadata['sender_id']);
        $this->assertLessThanOrEqual(120, mb_strlen($notification->metadata['excerpt']));
        $this->assertStringNotContainsString('<script>', $notification->message);
        $this->assertStringNotContainsString('<script>', $notification->metadata['excerpt']);
        $this->assertArrayNotHasKey('email', $notification->metadata);
        $this->assertArrayNotHasKey('password', $notification->metadata);
        $this->assertArrayNotHasKey('remember_token', $notification->metadata);

        $this->actingAs($fixture['member'])
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(route('notifications.open', $notification), false)
            ->assertDontSee('<script>alert("unsafe")</script>', false);
        $this->actingAs($fixture['member'])
            ->getJson(route('notifications.poll'))
            ->assertOk()
            ->assertJsonPath('notifications.0.url', $expectedUrl)
            ->assertJsonMissingPath('notifications.0.metadata');
        Queue::assertNothingPushed();
    }

    public function test_open_notification_marks_only_that_notification_read_and_outsider_cannot_open_chat(): void
    {
        $fixture = $this->workspaceFixture();
        $outsider = User::factory()->create();

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => $this->marker($fixture['member']),
            ])
            ->assertCreated();
        $notification = Notification::query()->sole();
        $otherNotification = Notification::create([
            'user_id' => $fixture['member']->id,
            'type' => 'test',
            'title' => 'Other',
            'message' => 'Other notification',
        ]);

        $this->actingAs($fixture['member'])
            ->get(route('notifications.open', $notification))
            ->assertRedirect($notification->url);

        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);

        $this->actingAs($outsider)
            ->get($notification->url)
            ->assertForbidden();
    }

    public function test_server_and_ajax_render_mentions_as_safe_labels_even_after_user_deletion(): void
    {
        $fixture = $this->workspaceFixture();
        $name = $fixture['member']->name;

        $response = $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => 'Halo '.$this->marker($fixture['member']).' <b>escaped</b>',
            ])
            ->assertCreated()
            ->assertJsonPath('content_segments.1.type', 'mention')
            ->assertJsonPath('content_segments.1.text', '@'.$name)
            ->assertJsonPath('plain_text', 'Halo @'.$name.' <b>escaped</b>');
        $this->assertStringNotContainsString(
            '<span',
            $response->json('plain_text'),
        );

        $fixture['workspace']->members()->detach($fixture['member']);
        $fixture['member']->delete();

        $html = $this->actingAs($fixture['owner'])
            ->get(route('workspaces.show', [
                'token' => $fixture['workspace']->token,
                'tab' => 'chat',
            ]))
            ->assertOk()
            ->assertSee('@'.$name)
            ->assertSee('&lt;b&gt;escaped&lt;/b&gt;', false)
            ->assertDontSee('<b>escaped</b>', false)
            ->getContent();

        $document = new \DOMDocument;
        @$document->loadHTML($html);
        $xpath = new \DOMXPath($document);
        $content = $xpath->query('//*[@data-chat-content]')->item(0);
        $mention = $xpath->query(
            "//*[@data-chat-content]//span[contains(@class, 'text-sky-700')]",
        )->item(0);

        $this->assertSame('Halo @'.$name.' <b>escaped</b>', $content->textContent);
        $this->assertSame('@'.$name, $mention->textContent);
    }

    public function test_old_notification_target_loads_and_highlights_message_without_marking_newer_messages_read(): void
    {
        $fixture = $this->workspaceFixture();

        $this->actingAs($fixture['owner'])
            ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                'content' => 'Target '.$this->marker($fixture['member']),
            ])
            ->assertCreated();
        $target = WorkspaceChatMessage::query()->sole();
        $notification = Notification::query()->sole();

        foreach (range(1, 35) as $number) {
            WorkspaceChatMessage::factory()
                ->for($fixture['workspace'])
                ->for($fixture['owner'])
                ->create(['content' => "Newer {$number}"]);
        }

        $response = $this->actingAs($fixture['member'])
            ->get($notification->url)
            ->assertOk()
            ->assertSee('data-target-message-id="'.$target->id.'"', false)
            ->assertSee('Target')
            ->assertSee('@'.$fixture['member']->name);

        $this->assertSame(
            $target->id,
            $fixture['workspace']->chatReads()
                ->where('user_id', $fixture['member']->id)
                ->value('last_read_message_id'),
        );
        $this->assertSame(35, $response->viewData('chatUnreadCount'));
        $javascript = file_get_contents(resource_path('js/workspace-chat.js'));
        $this->assertStringContainsString('scrollIntoView', $javascript);
        $this->assertStringContainsString('ring-sky-400', $javascript);
        $this->assertStringNotContainsString('innerHTML', $javascript);
    }

    public function test_transaction_rolls_back_message_when_mention_sync_fails(): void
    {
        $fixture = $this->workspaceFixture();
        DB::statement(
            'CREATE TRIGGER fail_workspace_chat_mention '
            .'BEFORE INSERT ON workspace_chat_message_mentions '
            ."BEGIN SELECT RAISE(ABORT, 'forced mention failure'); END",
        );
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($fixture['owner'])
                ->postJson(route('workspace-chat.messages.store', $fixture['workspace']), [
                    'content' => $this->marker($fixture['member']),
                ]);
            $this->fail('Expected mention synchronization to fail.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString(
                'forced mention failure',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseCount('workspace_chat_messages', 0);
        $this->assertDatabaseCount('workspace_chat_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
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

    private function marker(User $user): string
    {
        return "@[{$user->name}](user:{$user->id})";
    }
}
