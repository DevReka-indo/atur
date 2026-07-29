<?php

namespace App\Providers;

use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\User;
use App\Models\Workspace;
use App\Services\ProjectDiscussionService;
use App\Services\WorkloadService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        RateLimiter::for('workspace-member-search', function (Request $request): Limit {
            return Limit::perMinute(30)->by(
                implode(':', [$request->user()?->id, $request->route('token')]),
            );
        });
        RateLimiter::for('workspace-invitations', function (Request $request): Limit {
            return Limit::perMinute(10)->by(
                implode(':', [$request->user()?->id, $request->route('token')]),
            );
        });
        RateLimiter::for('workspace-invitation-resend', function (Request $request): Limit {
            return Limit::perMinute(5)->by(
                implode(':', [$request->user()?->id, $request->route('token')]),
            );
        });
        RateLimiter::for('workspace-chat-poll', function (Request $request): Limit {
            return Limit::perMinute(60)->by($this->workspaceChatRateLimitKey($request));
        });
        RateLimiter::for('workspace-chat-write', function (Request $request): Limit {
            return Limit::perMinute(30)->by($this->workspaceChatRateLimitKey($request));
        });
        RateLimiter::for('workspace-chat-mentions', function (Request $request): Limit {
            return Limit::perMinute(60)->by($this->workspaceChatRateLimitKey($request));
        });
        RateLimiter::for('project-discussion-poll', function (Request $request): Limit {
            return Limit::perMinute(120)->by($this->projectDiscussionRateLimitKey($request));
        });
        RateLimiter::for('project-discussion-write', function (Request $request): Limit {
            return Limit::perMinute(30)->by($this->projectDiscussionRateLimitKey($request));
        });
        RateLimiter::for('project-discussion-mentions', function (Request $request): Limit {
            return Limit::perMinute(60)->by($this->projectDiscussionRateLimitKey($request));
        });
        RateLimiter::for('workload-detail', function (Request $request): Limit {
            return Limit::perMinute(60)->by($request->user()?->id);
        });

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super_admin')
                ? true
                : null;
        });
        Gate::define(
            'view-workload-monitoring',
            fn (User $user): bool => app(WorkloadService::class)->canView($user),
        );
        View::composer('layouts.sidebar', function ($view): void {
            $view->with(
                'canViewWorkload',
                auth()->check()
                    && app(WorkloadService::class)->canView(auth()->user()),
            );
        });

        View::composer('layouts.app', function ($view) {

            if (auth()->check()) {

                $unreadCount = Notification::where('user_id', auth()->id())
                    ->whereNull('read_at')
                    ->count();

                $user = auth()->user();
                try {
                    $sidebarUnreadDiscussion = app(ProjectDiscussionService::class)
                        ->unreadTotalForUser($user);
                } catch (\Exception $e) {
                    $sidebarUnreadDiscussion = 0;
                }

            } else {
                $unreadCount = 0;
                $sidebarUnreadDiscussion = 0;
            }

            $view->with('unreadCount', $unreadCount);
            $view->with('sidebarUnreadDiscussion', $sidebarUnreadDiscussion);
        });
    }

    private function workspaceChatRateLimitKey(Request $request): string
    {
        $workspace = $request->route('workspace');
        $workspaceKey = $workspace instanceof Workspace
            ? $workspace->getKey()
            : $workspace;

        return implode(':', [$request->user()?->id, $workspaceKey]);
    }

    private function projectDiscussionRateLimitKey(Request $request): string
    {
        $project = $request->route('project');
        $thread = $request->route('thread');
        $projectKey = $project instanceof Project ? $project->getKey() : $project;
        $threadKey = $thread instanceof ProjectThread ? $thread->getKey() : $thread;

        return implode(':', [$request->user()?->id, $projectKey, $threadKey]);
    }
}
