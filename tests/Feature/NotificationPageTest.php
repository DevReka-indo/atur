<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class NotificationPageTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProjectTemplateTestSchema();
    }

    public function test_server_side_filters_show_only_the_expected_notification_categories(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $notifications = [
            'mention' => $this->notification($user, Notification::TYPE_WORKSPACE_CHAT_MENTION, 'Mention notice'),
            'task' => $this->notification($user, 'assignment', 'Task notice'),
            'project' => $this->notification($user, 'project_added', 'Project notice'),
            'alert' => $this->notification($user, 'member_overload', 'Alert notice'),
            'legacy' => $this->notification($user, 'legacy_notice', 'Legacy notice'),
            'read' => $this->notification($user, 'status_change', 'Read notice', now()),
        ];
        $this->notification($otherUser, 'member_overload', 'Other user notice');

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'all']))
            ->assertOk()
            ->assertSee($notifications['mention']->title)
            ->assertSee($notifications['legacy']->title)
            ->assertDontSee('Other user notice');

        $this->assertFilterShowsOnly($user, 'unread', [
            'Mention notice',
            'Task notice',
            'Project notice',
            'Alert notice',
            'Legacy notice',
        ], ['Read notice']);
        $this->assertFilterShowsOnly($user, 'mentions', ['Mention notice'], [
            'Task notice',
            'Project notice',
            'Alert notice',
        ]);
        $this->assertFilterShowsOnly($user, 'tasks', ['Task notice', 'Read notice'], [
            'Mention notice',
            'Project notice',
        ]);
        $this->assertFilterShowsOnly($user, 'projects', ['Project notice'], [
            'Task notice',
            'Alert notice',
        ]);
        $this->assertFilterShowsOnly($user, 'alerts', ['Alert notice'], [
            'Mention notice',
            'Project notice',
        ]);
    }

    public function test_filtered_pagination_keeps_the_filter_query_string(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 14) as $index) {
            $this->notification(
                $user,
                Notification::TYPE_PROJECT_DISCUSSION_MENTION,
                "Mention {$index}",
            );
        }

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'mentions']))
            ->assertOk()
            ->assertSee('filter=mentions', false)
            ->assertSee('page=2', false)
            ->assertSee('aria-current="page"', false);
    }

    public function test_read_delete_bulk_delete_and_open_actions_are_scoped_to_the_owner(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $first = $this->notification($owner, 'assignment', 'First');
        $second = $this->notification($owner, 'assignment', 'Second');
        $otherNotification = $this->notification($other, 'assignment', 'Other');

        $this->actingAs($owner)
            ->post(route('notifications.read', $otherNotification))
            ->assertNotFound();
        $this->assertNull($otherNotification->fresh()->read_at);

        $this->actingAs($owner)
            ->post(route('notifications.read', $first))
            ->assertRedirect();
        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($second->fresh()->read_at);

        $this->actingAs($owner)
            ->post(route('notifications.readAll'))
            ->assertRedirect();
        $this->assertNotNull($second->fresh()->read_at);
        $this->assertNull($otherNotification->fresh()->read_at);

        $openTarget = $this->notification($owner, 'legacy_notice', 'Open target');
        $this->actingAs($owner)
            ->get(route('notifications.open', $openTarget))
            ->assertRedirect(route('notifications.index'));
        $this->assertNotNull($openTarget->fresh()->read_at);

        $deletable = $this->notification($owner, 'assignment', 'Deletable');
        $this->actingAs($owner)
            ->delete(route('notifications.destroy', $deletable))
            ->assertRedirect();
        $this->assertModelMissing($deletable);

        $bulkOne = $this->notification($owner, 'assignment', 'Bulk one');
        $bulkTwo = $this->notification($owner, 'assignment', 'Bulk two');
        $this->actingAs($owner)
            ->delete(route('notifications.destroySelected'), [
                'notification_ids' => [
                    $bulkOne->id,
                    $bulkTwo->id,
                    $otherNotification->id,
                ],
            ])
            ->assertRedirect();

        $this->assertModelMissing($bulkOne);
        $this->assertModelMissing($bulkTwo);
        $this->assertModelExists($otherNotification);
    }

    public function test_page_has_accessible_cards_actions_empty_state_and_responsive_layout(): void
    {
        $user = User::factory()->create();
        $this->notification(
            $user,
            Notification::TYPE_PROJECT_DISCUSSION_MENTION,
            'Project mention',
        );

        $response = $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Stay updated with project activity, discussions, and deadlines.')
            ->assertSee('aria-label="Unread notification"', false)
            ->assertSee('fa-ellipsis-vertical', false)
            ->assertSee('data-notification-menu', false)
            ->assertSee('data-notification-bulk-submit', false)
            ->assertSee('disabled', false)
            ->assertSee('lg:grid-cols-3', false)
            ->assertSee('lg:col-span-2', false)
            ->assertSee('aria-label="Notification actions"', false);

        $this->assertStringNotContainsString('bg-pink', $response->getContent());

        $notificationScript = file_get_contents(resource_path('js/notifications.js'));

        $this->assertStringContainsString('document.createElement', $notificationScript);
        $this->assertStringContainsString('textContent', $notificationScript);
        $this->assertStringNotContainsString('innerHTML', $notificationScript);

        Notification::query()->delete();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('No notifications yet')
            ->assertSee("You're all caught up. New project activity will appear here.", false);

        $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => 'mentions']))
            ->assertOk()
            ->assertSee('No notifications match this filter.')
            ->assertSee('Reset filter');
    }

    public function test_deadline_panel_uses_due_state_labels_and_keeps_tasks_openable(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['created_by' => $user->id]);
        $project = Project::factory()->create([
            'workspace_id' => $workspace->id,
            'created_by' => $user->id,
        ]);
        $task = Task::factory()->create([
            'project_id' => $project->id,
            'created_by' => $user->id,
            'name' => 'Deadline task',
            'due_date' => today(),
        ]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Deadline task')
            ->assertSee('Due today')
            ->assertSee(route('tasks.show', $task), false);
    }

    public function test_notification_page_query_count_is_stable_as_rows_increase(): void
    {
        $user = User::factory()->create();
        $this->notification($user, 'assignment', 'Initial');

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();
        $initialQueryCount = $this->queryCountForNotificationPage($user);

        foreach (range(1, 30) as $index) {
            $this->notification($user, 'assignment', "Additional {$index}");
        }

        $largerQueryCount = $this->queryCountForNotificationPage($user);

        $this->assertSame($initialQueryCount, $largerQueryCount);
    }

    private function assertFilterShowsOnly(
        User $user,
        string $filter,
        array $visibleTitles,
        array $hiddenTitles,
    ): void {
        $response = $this->actingAs($user)
            ->get(route('notifications.index', ['filter' => $filter]))
            ->assertOk();

        foreach ($visibleTitles as $title) {
            $response->assertSee($title);
        }
        foreach ($hiddenTitles as $title) {
            $response->assertDontSee($title);
        }
    }

    private function notification(
        User $user,
        string $type,
        string $title,
        mixed $readAt = null,
    ): Notification {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => $title,
            'message' => "{$title} description",
            'read_at' => $readAt,
        ]);
    }

    private function queryCountForNotificationPage(User $user): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk();

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $queryCount;
    }
}
