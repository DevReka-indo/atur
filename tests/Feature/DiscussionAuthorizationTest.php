<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\ThreadUserRead;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\QueryException;
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

    public function test_project_admin_and_member_can_load_messages_while_outsider_cannot(): void
    {
        $this->createMessage($this->threadA, $this->projectAManager, 'Visible to contributors');

        foreach ([$this->projectAManager, $this->projectAMember] as $user) {
            $this->actingAs($user)
                ->getJson(route('discussion.messages.index', [$this->projectA, $this->threadA]))
                ->assertOk()
                ->assertJsonCount(1, 'messages');
        }

        $this->actingAs($this->outsider)
            ->getJson(route('discussion.messages.index', [$this->projectA, $this->threadA]))
            ->assertForbidden();
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
            ->getJson(route('discussion.messages.index', [$this->projectA, $this->threadA]))
            ->assertForbidden();

        $this->actingAs($this->projectAViewer)
            ->postJson(route('discussion.messages.read', [$this->projectA, $this->threadA]), [
                'last_read_message_id' => 1,
            ])
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
            ->assertJsonPath('content', 'Updated message')
            ->assertJsonPath('can_edit', true)
            ->assertJsonPath('can_delete', true)
            ->assertJsonPath('edited_at', fn (mixed $editedAt): bool => is_string($editedAt));

        $this->assertSame('Updated message', $message->fresh()->content);
        $this->assertNotNull($message->fresh()->edited_at);

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

    public function test_chat_initial_load_contains_only_latest_thirty_messages_in_ascending_order(): void
    {
        foreach (range(1, 65) as $index) {
            $this->createMessage($this->threadA, $this->projectAManager, "Message {$index}");
        }

        $response = $this->actingAs($this->projectAMember)
            ->get(route('discussion.chat', [$this->projectA, $this->threadA]))
            ->assertOk()
            ->assertViewHas('hasMoreOlder', true);

        $messages = $response->viewData('messages');
        $this->assertCount(30, $messages);
        $this->assertSame(range(36, 65), $messages->pluck('content')
            ->map(fn (string $content): int => (int) str($content)->after('Message ')->toString())
            ->all());

        $latestMessage = $this->threadA->messages()->latest('id')->firstOrFail();
        $this->assertDatabaseHas('thread_user_reads', [
            'thread_id' => $this->threadA->id,
            'user_id' => $this->projectAMember->id,
            'last_read_message_id' => $latestMessage->id,
        ]);
    }

    public function test_before_and_after_message_cursors_return_stable_scoped_pages(): void
    {
        $messages = collect(range(1, 65))
            ->map(fn (int $index): ProjectThreadMessage => $this->createMessage(
                $this->threadA,
                $this->projectAManager,
                "Cursor {$index}",
            ));

        $beforeResponse = $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.messages.index', [
                $this->projectA,
                $this->threadA,
                'before_id' => $messages[35]->id,
            ]))
            ->assertOk()
            ->assertJsonPath('has_more_older', true)
            ->assertJsonCount(30, 'messages');

        $this->assertSame(
            $messages->slice(5, 30)->pluck('id')->all(),
            collect($beforeResponse->json('messages'))->pluck('id')->all(),
        );

        $afterResponse = $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.messages.index', [
                $this->projectA,
                $this->threadA,
                'after_id' => $messages[59]->id,
            ]))
            ->assertOk()
            ->assertJsonPath('has_more_newer', false)
            ->assertJsonCount(5, 'messages');

        $this->assertSame(
            $messages->slice(60)->pluck('id')->all(),
            collect($afterResponse->json('messages'))->pluck('id')->all(),
        );

        $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.messages.index', [
                $this->projectA,
                $this->threadB,
                'after_id' => $messages[0]->id,
            ]))
            ->assertNotFound();
    }

    public function test_message_cursor_validation_and_response_do_not_expose_sensitive_user_data(): void
    {
        $message = $this->createMessage($this->threadA, $this->projectAManager, 'Safe response');

        $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.messages.index', [
                $this->projectA,
                $this->threadA,
                'before_id' => $message->id,
                'after_id' => $message->id,
            ]))
            ->assertUnprocessable();

        $response = $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.messages.index', [$this->projectA, $this->threadA]))
            ->assertOk()
            ->assertJsonMissingPath('messages.0.sender.email')
            ->assertJsonMissingPath('messages.0.sender.password')
            ->assertJsonMissingPath('messages.0.sender.remember_token');

        $this->assertSame('Safe response', $response->json('messages.0.content'));
    }

    public function test_message_content_is_trimmed_and_rejects_whitespace_or_more_than_one_thousand_characters(): void
    {
        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => " \n\t ",
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => str_repeat('a', 1001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => '  Trimmed message  ',
            ])
            ->assertOk()
            ->assertJsonPath('content', 'Trimmed message');
    }

    public function test_read_cursor_marks_only_through_the_selected_message_and_excludes_own_messages(): void
    {
        $first = $this->createMessage($this->threadA, $this->projectAManager, 'First unread');
        $own = $this->createMessage($this->threadA, $this->projectAMember, 'Own message');
        $last = $this->createMessage($this->threadA, $this->projectAManager, 'Still unread');
        $otherThreadMessage = $this->createMessage($this->threadB, $this->projectBMember, 'Other thread');

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.read', [$this->projectA, $this->threadA]), [
                'last_read_message_id' => $own->id,
            ])
            ->assertOk()
            ->assertJsonPath('last_read_message_id', $own->id)
            ->assertJsonPath('unread_count', 1);

        $this->assertDatabaseHas('thread_user_reads', [
            'thread_id' => $this->threadA->id,
            'user_id' => $this->projectAMember->id,
            'last_read_message_id' => $own->id,
        ]);
        $this->assertDatabaseMissing('thread_user_reads', [
            'thread_id' => $this->threadB->id,
            'user_id' => $this->projectAMember->id,
        ]);
        $this->assertLessThan($otherThreadMessage->id, $last->id);
        $this->assertLessThan($last->id, $own->id);
        $this->assertLessThan($own->id, $first->id);
    }

    public function test_id_read_cursor_remains_accurate_when_messages_share_the_same_timestamp(): void
    {
        $timestamp = now()->startOfSecond();
        $first = $this->createMessage($this->threadA, $this->projectAManager, 'Same second one');
        $second = $this->createMessage($this->threadA, $this->projectAManager, 'Same second two');
        $first->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->save();
        $second->forceFill(['created_at' => $timestamp, 'updated_at' => $timestamp])->save();

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.read', [$this->projectA, $this->threadA]), [
                'last_read_message_id' => $first->id,
            ])
            ->assertOk()
            ->assertJsonPath('unread_count', 1);
    }

    public function test_legacy_timestamp_read_state_remains_compatible_until_an_id_cursor_is_recorded(): void
    {
        $readMessage = $this->createMessage($this->threadA, $this->projectAManager, 'Legacy read');
        $readMessage->forceFill(['created_at' => now()->subMinute()])->save();
        $unreadMessage = $this->createMessage($this->threadA, $this->projectAManager, 'Legacy unread');

        ThreadUserRead::create([
            'thread_id' => $this->threadA->id,
            'user_id' => $this->projectAMember->id,
            'last_read_at' => $readMessage->fresh()->created_at,
            'last_read_message_id' => null,
        ]);

        $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.unread', $this->projectA))
            ->assertOk()
            ->assertJsonFragment([
                'id' => $this->threadA->id,
                'unread_count' => 1,
            ]);

        $this->assertGreaterThan($readMessage->id, $unreadMessage->id);
    }

    public function test_message_endpoint_query_count_does_not_grow_with_message_count(): void
    {
        $this->createMessage($this->threadA, $this->projectAManager, 'Initial query message');
        $initialCount = $this->messageEndpointQueryCount();

        foreach (range(1, 100) as $index) {
            $this->createMessage($this->threadA, $this->projectAManager, "Expanded {$index}");
        }

        $expandedCount = $this->messageEndpointQueryCount();

        $this->assertLessThanOrEqual($initialCount, $expandedCount);
    }

    public function test_mention_candidates_are_scoped_to_active_project_contributors(): void
    {
        $this->projectAMember->update([
            'name' => 'Searchable Project Member',
            'email' => 'searchable.member@example.test',
        ]);

        foreach ([$this->projectAManager, $this->projectBMember] as $actor) {
            $this->actingAs($actor)
                ->getJson(route('discussion.mention-candidates', [
                    $this->projectA,
                    $this->threadA,
                    'search' => 'Searchable',
                ]))
                ->assertOk()
                ->assertJsonPath('members.0.id', $this->projectAMember->id)
                ->assertJsonPath('members.0.role', 'Member')
                ->assertJsonMissingPath('members.0.email')
                ->assertJsonMissingPath('members.0.password')
                ->assertJsonMissingPath('members.0.remember_token');
        }

        $this->actingAs($this->projectAManager)
            ->getJson(route('discussion.mention-candidates', [
                $this->projectA,
                $this->threadA,
                'search' => $this->projectAMember->email,
            ]))
            ->assertOk()
            ->assertJsonPath('members.0.id', $this->projectAMember->id);

        foreach ([$this->projectAViewer, $this->outsider] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)
                ->getJson(route('discussion.mention-candidates', [
                    $this->projectA,
                    $this->threadA,
                ]))
                ->assertForbidden();
        }

        $candidateIds = collect(
            $this->actingAs($this->projectAMember)
                ->getJson(route('discussion.mention-candidates', [
                    $this->projectA,
                    $this->threadA,
                ]))
                ->assertOk()
                ->json('members'),
        )->pluck('id');
        $this->assertNotContains($this->projectAViewer->id, $candidateIds);
        $this->assertNotContains($this->outsider->id, $candidateIds);
        $this->assertNotContains($this->projectAMember->id, $candidateIds);
    }

    public function test_mention_candidate_query_count_is_stable(): void
    {
        $initialCount = $this->mentionCandidateQueryCount();

        $members = User::factory()->count(8)->create();
        $this->projectA->members()->attach(
            $members->mapWithKeys(fn (User $user): array => [
                $user->id => [
                    'role' => Project::ROLE_MEMBER,
                    'joined_at' => now(),
                ],
            ])->all(),
        );

        $this->assertLessThanOrEqual(
            $initialCount,
            $this->mentionCandidateQueryCount(),
        );
    }

    public function test_store_normalizes_mentions_and_notifies_only_unique_valid_targets(): void
    {
        $content = 'Halo '.$this->marker($this->projectAMember)
            .' lagi '.$this->marker($this->projectAMember)
            .' self '.$this->marker($this->projectAManager)
            .' viewer '.$this->marker($this->projectAViewer)
            .' outsider '.$this->marker($this->outsider);

        $response = $this->actingAs($this->projectAManager)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => $content,
            ])
            ->assertOk()
            ->assertJsonPath('content_segments.1.type', 'mention')
            ->assertJsonPath('content_segments.1.user_id', $this->projectAMember->id);

        $message = ProjectThreadMessage::query()->sole();
        $this->assertEqualsCanonicalizing(
            [$this->projectAMember->id, $this->projectAManager->id],
            $message->mentionedUsers()->pluck('users.id')->all(),
        );
        $this->assertDatabaseCount('notifications', 1);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->projectAMember->id,
            'type' => Notification::TYPE_PROJECT_DISCUSSION_MENTION,
            'project_thread_id' => $this->threadA->id,
            'project_thread_message_id' => $message->id,
        ]);
        $this->assertDatabaseMissing('notifications', [
            'user_id' => $this->projectAManager->id,
        ]);
        $notification = Notification::query()->sole();
        $this->assertSame(
            route('discussion.chat', [
                'project' => $this->projectA,
                'thread' => $this->threadA,
                'message' => $message->id,
            ], false),
            $notification->url,
        );
        $this->assertSame($this->projectA->name, $notification->metadata['project_name']);
        $this->assertSame($this->threadA->title, $notification->metadata['thread_title']);
        $this->assertSame('project_discussion', $notification->metadata['source']);
        $this->assertLessThanOrEqual(120, mb_strlen($notification->metadata['excerpt']));
        $this->assertArrayNotHasKey('email', $notification->metadata);
        $this->assertArrayNotHasKey('password', $notification->metadata);
        $this->assertStringContainsString('@'.$this->projectAViewer->name, $message->content);
        $this->assertStringNotContainsString(
            '(user:'.$this->projectAViewer->id.')',
            $message->content,
        );
        $this->assertStringNotContainsString(
            '(user:'.$this->outsider->id.')',
            $response->json('plain_text'),
        );
    }

    public function test_message_without_mentions_works_and_more_than_ten_mentions_rolls_back(): void
    {
        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => 'Pesan biasa.',
            ])
            ->assertOk()
            ->assertJsonPath('plain_text', 'Pesan biasa.');

        $members = User::factory()->count(11)->create();
        $this->projectA->members()->attach(
            $members->mapWithKeys(fn (User $user): array => [
                $user->id => [
                    'role' => Project::ROLE_MEMBER,
                    'joined_at' => now(),
                ],
            ])->all(),
        );

        $this->actingAs($this->projectAMember)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => $members->map(fn (User $user): string => $this->marker($user))->implode(' '),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->assertDatabaseCount('project_thread_messages', 1);
        $this->assertDatabaseCount('project_thread_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_mentions_render_safely_and_keep_the_label_snapshot_after_target_deletion(): void
    {
        $mentionedName = $this->projectAMember->name;

        $this->actingAs($this->projectAManager)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => 'Halo '.$this->marker($this->projectAMember).' <b>escaped</b>',
            ])
            ->assertOk()
            ->assertJsonPath('plain_text', 'Halo @'.$mentionedName.' <b>escaped</b>')
            ->assertJsonPath('content_segments.1.text', '@'.$mentionedName);

        $this->projectA->members()->detach($this->projectAMember);
        $this->projectAMember->delete();

        $response = $this->actingAs($this->projectAManager)
            ->get(route('discussion.chat', [$this->projectA, $this->threadA]))
            ->assertOk()
            ->assertSee('@'.$mentionedName)
            ->assertSee('&lt;b&gt;escaped&lt;/b&gt;', false)
            ->assertDontSee('<b>escaped</b>', false);

        $this->assertStringContainsString('text-indigo-700', $response->getContent());
    }

    public function test_update_syncs_mentions_without_renotification_spam(): void
    {
        $this->actingAs($this->projectAManager)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => $this->marker($this->projectAMember),
            ])
            ->assertOk();
        $message = ProjectThreadMessage::query()->sole();

        $this->actingAs($this->projectAManager)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => $this->marker($this->projectAMember).' '.$this->marker($this->projectBMember),
            ])
            ->assertOk();
        $this->assertDatabaseCount('notifications', 2);

        $this->actingAs($this->projectAManager)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => $this->marker($this->projectBMember),
            ])
            ->assertOk();
        $this->assertSame(
            [$this->projectBMember->id],
            $message->mentionedUsers()->pluck('users.id')->all(),
        );

        $this->actingAs($this->projectAManager)
            ->patchJson(route('messages.update', [$this->projectA, $this->threadA, $message]), [
                'content' => $this->marker($this->projectAMember).' '.$this->marker($this->projectBMember),
            ])
            ->assertOk();
        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_delete_cascades_mentions_and_keeps_notification_url_safe(): void
    {
        $this->actingAs($this->projectAManager)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => $this->marker($this->projectAMember),
            ])
            ->assertOk();
        $message = ProjectThreadMessage::query()->sole();
        $notification = Notification::query()->sole();
        $url = $notification->url;

        $this->actingAs($this->projectAManager)
            ->deleteJson(route('messages.destroy', [$this->projectA, $this->threadA, $message]))
            ->assertOk();

        $this->assertDatabaseCount('project_thread_message_mentions', 0);
        $this->assertDatabaseHas('notifications', [
            'id' => $notification->id,
            'project_thread_message_id' => null,
            'url' => $url,
        ]);

        $this->actingAs($this->projectAMember)
            ->get(route('notifications.open', $notification))
            ->assertRedirect($url);
        $this->createMessage($this->threadA, $this->projectAManager, 'Newer unread message');
        $this->actingAs($this->projectAMember)
            ->get($url)
            ->assertOk()
            ->assertSee('The message referenced by this notification is no longer available.');
        $this->assertDatabaseMissing('thread_user_reads', [
            'thread_id' => $this->threadA->id,
            'user_id' => $this->projectAMember->id,
        ]);
    }

    public function test_notification_target_loads_old_message_and_marks_read_only_through_target(): void
    {
        $this->actingAs($this->projectAManager)
            ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                'content' => 'Target '.$this->marker($this->projectAMember),
            ])
            ->assertOk();
        $target = ProjectThreadMessage::query()->sole();
        $notification = Notification::query()->sole();

        foreach (range(1, 35) as $index) {
            $this->createMessage($this->threadA, $this->projectAManager, "Newer {$index}");
        }

        $this->actingAs($this->outsider)
            ->get(route('notifications.open', $notification))
            ->assertNotFound();

        $this->actingAs($this->projectAMember)
            ->get(route('notifications.open', $notification))
            ->assertRedirect($notification->url);
        $this->assertNotNull($notification->fresh()->read_at);

        $response = $this->actingAs($this->projectAMember)
            ->get($notification->url)
            ->assertOk()
            ->assertSee('data-target-message-id="'.$target->id.'"', false)
            ->assertSee('@'.$this->projectAMember->name);

        $this->assertSame(
            $target->id,
            ThreadUserRead::query()
                ->where('thread_id', $this->threadA->id)
                ->where('user_id', $this->projectAMember->id)
                ->value('last_read_message_id'),
        );
        $this->assertSame(35, $this->app->make(\App\Services\ProjectDiscussionService::class)
            ->unreadCountForThread($this->threadA, $this->projectAMember));
        $this->assertTrue($response->viewData('targetMessageMissing') === false);
    }

    public function test_mention_transaction_rolls_back_when_pivot_sync_fails(): void
    {
        DB::statement(
            'CREATE TRIGGER fail_project_discussion_mention '
            .'BEFORE INSERT ON project_thread_message_mentions '
            ."BEGIN SELECT RAISE(ABORT, 'forced mention failure'); END",
        );
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->projectAManager)
                ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                    'content' => $this->marker($this->projectAMember),
                ]);
            $this->fail('Expected mention synchronization to fail.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('forced mention failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('project_thread_messages', 0);
        $this->assertDatabaseCount('project_thread_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_mention_transaction_rolls_back_when_notification_creation_fails(): void
    {
        DB::statement(
            'CREATE TRIGGER fail_project_discussion_notification '
            .'BEFORE INSERT ON notifications '
            ."BEGIN SELECT RAISE(ABORT, 'forced notification failure'); END",
        );
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->projectAManager)
                ->postJson(route('discussion.messages.store', [$this->projectA, $this->threadA]), [
                    'content' => $this->marker($this->projectAMember),
                ]);
            $this->fail('Expected notification creation to fail.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('forced notification failure', $exception->getMessage());
        }

        $this->assertDatabaseCount('project_thread_messages', 0);
        $this->assertDatabaseCount('project_thread_message_mentions', 0);
        $this->assertDatabaseCount('notifications', 0);
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

    private function messageEndpointQueryCount(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->projectAMember)
            ->getJson(route('discussion.messages.index', [$this->projectA, $this->threadA]))
            ->assertOk();
        $count = $this->discussionQueryCount();
        DB::disableQueryLog();

        return $count;
    }

    private function mentionCandidateQueryCount(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($this->projectAManager)
            ->getJson(route('discussion.mention-candidates', [
                $this->projectA,
                $this->threadA,
            ]))
            ->assertOk();
        $count = $this->discussionQueryCount();
        DB::disableQueryLog();

        return $count;
    }

    private function marker(User $user): string
    {
        return "@[{$user->name}](user:{$user->id})";
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
            $table->timestamp('edited_at')->nullable();
            $table->timestamps();
        });

        Schema::create('project_thread_message_mentions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_thread_message_id')
                ->constrained('project_thread_messages')
                ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['project_thread_message_id', 'user_id']);
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

        Schema::create('notifications', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('type');
            $table->string('title');
            $table->text('message');
            $table->foreignId('task_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('project_thread_id')
                ->nullable()
                ->constrained('project_threads')
                ->nullOnDelete();
            $table->foreignId('project_thread_message_id')
                ->nullable()
                ->constrained('project_thread_messages')
                ->nullOnDelete();
            $table->string('url', 1000)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->unique([
                'user_id',
                'type',
                'project_thread_message_id',
            ]);
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
