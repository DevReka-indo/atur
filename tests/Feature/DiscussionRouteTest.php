<?php

namespace Tests\Feature;

use App\Http\Controllers\DiscussionController;
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Tests\TestCase;

class DiscussionRouteTest extends TestCase
{
    public function test_unread_sidebar_matches_its_static_route(): void
    {
        $this->assertSame(
            'discussion.unread-sidebar',
            $this->matchedRouteName('/discussion/unread-sidebar')
        );
    }

    public function test_project_unread_matches_its_named_route(): void
    {
        $this->assertSame(
            'discussion.unread',
            $this->matchedRouteName('/discussion/123/unread')
        );
    }

    public function test_project_unread_counts_matches_its_named_route(): void
    {
        $this->assertSame(
            'discussion.unread-counts',
            $this->matchedRouteName('/discussion/123/unread-counts')
        );
    }

    public function test_canonical_message_store_route_has_the_expected_contract(): void
    {
        $this->assertSame(
            '/discussion/123/456/messages',
            route('discussion.messages.store', ['project' => 123, 'thread' => 456], absolute: false)
        );

        $canonicalRoute = $this->router()->getRoutes()->match(
            Request::create('/discussion/123/456/messages', 'POST')
        );

        $this->assertSame('discussion.messages.store', $canonicalRoute->getName());
        $this->assertSame('discussion/{project}/{thread}/messages', $canonicalRoute->uri());
        $this->assertSame(DiscussionController::class.'@storeMessage', $canonicalRoute->getActionName());
        $this->assertContains('POST', $canonicalRoute->methods());
        $this->assertContains('throttle:project-discussion-write', $canonicalRoute->gatherMiddleware());
        $this->assertTrue($canonicalRoute->enforcesScopedBindings());
    }

    public function test_message_cursor_and_read_routes_have_the_expected_contract(): void
    {
        $indexRoute = $this->router()->getRoutes()->match(
            Request::create('/discussion/123/456/messages', 'GET')
        );
        $readRoute = $this->router()->getRoutes()->match(
            Request::create('/discussion/123/456/read', 'POST')
        );

        $this->assertSame('discussion.messages.index', $indexRoute->getName());
        $this->assertSame(DiscussionController::class.'@messages', $indexRoute->getActionName());
        $this->assertContains('throttle:project-discussion-poll', $indexRoute->gatherMiddleware());
        $this->assertTrue($indexRoute->enforcesScopedBindings());

        $this->assertSame('discussion.messages.read', $readRoute->getName());
        $this->assertSame(DiscussionController::class.'@markThreadRead', $readRoute->getActionName());
        $this->assertContains('throttle:project-discussion-poll', $readRoute->gatherMiddleware());
        $this->assertTrue($readRoute->enforcesScopedBindings());
    }

    public function test_mention_candidate_route_is_scoped_and_throttled(): void
    {
        $route = $this->router()->getRoutes()->match(
            Request::create('/discussion/123/456/mention-candidates', 'GET')
        );

        $this->assertSame('discussion.mention-candidates', $route->getName());
        $this->assertSame(DiscussionController::class.'@mentionCandidates', $route->getActionName());
        $this->assertContains('throttle:project-discussion-mentions', $route->gatherMiddleware());
        $this->assertTrue($route->enforcesScopedBindings());
    }

    public function test_legacy_message_store_endpoint_remains_unnamed(): void
    {
        $legacyRoute = $this->router()->getRoutes()->match(
            Request::create('/discussion/123/thread/456/messages', 'POST')
        );

        $this->assertNull($legacyRoute->getName());
        $this->assertSame('discussion/{project}/thread/{thread}/messages', $legacyRoute->uri());
        $this->assertSame(DiscussionController::class.'@storeMessage', $legacyRoute->getActionName());
        $this->assertContains('POST', $legacyRoute->methods());
    }

    public function test_canonical_message_store_route_name_is_unique(): void
    {
        $matchingRoutes = collect($this->router()->getRoutes()->getRoutes())
            ->filter(fn ($route): bool => $route->getName() === 'discussion.messages.store');

        $this->assertCount(1, $matchingRoutes);
    }

    public function test_chat_frontend_posts_messages_to_the_canonical_named_route(): void
    {
        $chatView = file_get_contents(resource_path('views/discussion/chat.blade.php'));
        $discussionScript = file_get_contents(resource_path('js/project-discussion.js'));

        $this->assertIsString($chatView);
        $this->assertIsString($discussionScript);
        $this->assertMatchesRegularExpression(
            '/route\s*\(\s*[\'"]discussion\.messages\.store[\'"]/',
            $chatView,
        );
        $this->assertStringContainsString('root.dataset.messageStoreUrl', $discussionScript);
        $this->assertStringNotContainsString('/discussion/${PROJECT_ID}/thread/${THREAD_ID}/messages', $discussionScript);
    }

    public function test_thread_preview_uses_safe_dom_text_rendering(): void
    {
        $discussionScript = file_get_contents(resource_path('js/project-discussion.js'));
        $mentionComposer = file_get_contents(resource_path('js/mention-composer.js'));

        $this->assertIsString($discussionScript);
        $this->assertIsString($mentionComposer);
        $this->assertStringContainsString('preview.textContent =', $discussionScript);
        $this->assertStringContainsString('document.createTextNode(segment.text)', $mentionComposer);
        $this->assertStringContainsString('mention.textContent = segment.text', $mentionComposer);
        $this->assertStringNotContainsString('innerHTML', $discussionScript);
        $this->assertStringNotContainsString('innerHTML', $mentionComposer);
    }

    public function test_chat_frontend_contains_cursor_polling_and_visibility_controls(): void
    {
        $discussionScript = file_get_contents(resource_path('js/project-discussion.js'));

        $this->assertIsString($discussionScript);
        $this->assertStringContainsString("url.searchParams.set('before_id'", $discussionScript);
        $this->assertStringContainsString("url.searchParams.set('after_id'", $discussionScript);
        $this->assertStringContainsString('new AbortController()', $discussionScript);
        $this->assertStringContainsString('document.hidden', $discussionScript);
        $this->assertStringContainsString('pollingDelays = [5000, 10000, 20000, 30000]', $discussionScript);
        foreach (['ArrowDown', 'ArrowUp', 'Enter', 'Escape'] as $key) {
            $this->assertStringContainsString($key, $discussionScript);
        }
        $this->assertStringContainsString('root.dataset.mentionCandidatesUrl', $discussionScript);
        $this->assertStringContainsString('scrollIntoView', $discussionScript);
        $this->assertStringContainsString('ring-indigo-400', $discussionScript);
        $this->assertStringNotContainsString('attachment', $discussionScript);
    }

    public function test_thread_preview_uses_the_eager_loaded_last_message(): void
    {
        $threadView = file_get_contents(resource_path('views/discussion/partials/project/_thread-item.blade.php'));

        $this->assertIsString($threadView);
        $this->assertStringContainsString('$thread->messages->first()', $threadView);
        $this->assertStringNotContainsString('$thread->messages()->latest()->first()', $threadView);
    }

    public function test_discussion_views_use_the_expected_partial_structure(): void
    {
        $projectPartial = 'discussion.partials.project._index';
        $discussionShow = file_get_contents(resource_path('views/discussion/show.blade.php'));
        $projectShow = file_get_contents(resource_path('views/projects/show.blade.php'));

        $this->assertStringContainsString($projectPartial, $discussionShow);
        $this->assertStringContainsString($projectPartial, $projectShow);

        foreach ([
            'index/_header',
            'index/_filters',
            'index/_project-list',
            'index/_project-card',
            'project/_index',
            'project/_header',
            'project/_thread-list',
            'project/_thread-item',
            'project/_empty-state',
            'project/_create-thread-modal',
            'chat/_header',
            'chat/_message-list',
            'chat/_message-item',
            'chat/_composer',
            'chat/_load-older',
            'chat/_new-messages-indicator',
            'chat/_edit-message-modal',
            'chat/_delete-message-modal',
            'chat/_empty-state',
            'chat/_mention-suggestions',
            'chat/_target-message-state',
        ] as $partial) {
            $this->assertFileExists(resource_path("views/discussion/partials/{$partial}.blade.php"));
        }
    }

    public function test_project_discussion_migrations_use_short_explicit_constraint_names(): void
    {
        $mentionMigration = file_get_contents(database_path(
            'migrations/2026_07_29_110623_create_project_thread_message_mentions_table.php',
        ));
        $notificationMigration = file_get_contents(database_path(
            'migrations/2026_07_29_110623_add_project_discussion_context_to_notifications_table.php',
        ));

        foreach ([
            'pt_mentions_message_fk',
            'pt_mentions_user_fk',
            'pt_mentions_message_user_unique',
            'pt_notif_thread_fk',
            'pt_notif_message_fk',
            'pt_notif_message_user_unique',
        ] as $constraint) {
            $this->assertLessThanOrEqual(64, strlen($constraint));
            $this->assertTrue(
                str_contains($mentionMigration, $constraint)
                    || str_contains($notificationMigration, $constraint),
            );
        }
    }

    public function test_notification_view_supports_project_discussion_mentions(): void
    {
        $view = file_get_contents(resource_path('views/notifications/index.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('TYPE_PROJECT_DISCUSSION_MENTION', $view);
        $this->assertStringContainsString('fa-comments', $view);
        $this->assertStringContainsString("data_get(\$notif->metadata, 'project_name')", $view);
        $this->assertStringContainsString("route('notifications.open'", $view);
    }

    public function test_discussion_index_and_show_routes_remain_available(): void
    {
        $this->assertSame('discussion.index', $this->matchedRouteName('/discussion'));
        $this->assertSame('discussion.show', $this->matchedRouteName('/discussion/123'));
    }

    public function test_projects_tasks_json_is_registered_once(): void
    {
        $matchingRoutes = collect($this->router()->getRoutes()->getRoutes())
            ->filter(fn ($route): bool => $route->uri() === 'projects/{id}/tasks-json'
                && in_array('GET', $route->methods(), true));

        $this->assertCount(1, $matchingRoutes);
        $this->assertSame('projects.tasks.json', $matchingRoutes->first()->getName());
    }

    private function matchedRouteName(string $uri): ?string
    {
        return $this->router()->getRoutes()->match(Request::create($uri, 'GET'))->getName();
    }

    private function router(): Router
    {
        return app(Router::class);
    }
}
