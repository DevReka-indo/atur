<?php

namespace Tests\Feature;

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

    public function test_messages_store_generates_the_canonical_uri_and_keeps_the_legacy_alias(): void
    {
        $this->assertSame(
            '/discussion/123/thread/456/messages',
            route('messages.store', ['project' => 123, 'thread' => 456], absolute: false)
        );

        $legacyRoute = $this->router()->getRoutes()->match(
            Request::create('/discussion/123/456/messages', 'POST')
        );

        $this->assertNull($legacyRoute->getName());
        $this->assertSame('discussion/{project}/{thread}/messages', $legacyRoute->uri());
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
