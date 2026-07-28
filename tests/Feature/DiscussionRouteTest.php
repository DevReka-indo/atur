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

        $this->assertIsString($chatView);
        $this->assertMatchesRegularExpression(
            '/route\s*\(\s*[\'"]discussion\.messages\.store[\'"]/',
            $chatView,
        );
        $this->assertStringContainsString('fetch(MESSAGE_STORE_URL', $chatView);
        $this->assertStringNotContainsString('fetch(`/discussion/${PROJECT_ID}/thread/${THREAD_ID}/messages`,', $chatView);
    }

    public function test_thread_preview_uses_safe_dom_text_rendering(): void
    {
        $discussionView = file_get_contents(resource_path('views/discussion/show.blade.php'));

        $this->assertIsString($discussionView);
        $this->assertStringContainsString('sender.textContent =', $discussionView);
        $this->assertStringContainsString('document.createTextNode(', $discussionView);
        $this->assertDoesNotMatchRegularExpression('/preview\.innerHTML\s*=/', $discussionView);
    }

    public function test_thread_preview_uses_the_eager_loaded_last_message(): void
    {
        $discussionView = file_get_contents(resource_path('views/discussion/show.blade.php'));

        $this->assertIsString($discussionView);
        $this->assertStringContainsString('$thread->messages->first()', $discussionView);
        $this->assertStringNotContainsString('$thread->messages()->latest()->first()', $discussionView);
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
