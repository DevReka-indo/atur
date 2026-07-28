<?php

namespace App\Providers;

use App\Models\Project;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\Workspace;
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

        Gate::before(function (User $user): ?bool {
            return $user->hasRole('super_admin')
                ? true
                : null;
        });

        View::composer('*', function ($view) {

            if (auth()->check()) {

                $unreadCount = UserNotification::where('user_id', auth()->id())
                    ->whereNull('read_at')
                    ->count();

                $user = auth()->user();
                $sidebarUnreadDiscussion = 0;

                try {
                    $projects = Project::where(function ($q) use ($user) {
                        $q->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                            ->orWhere('created_by', $user->id);
                    })
                        ->with(['threads.userReads' => fn ($q) => $q->where('user_id', $user->id)])
                        ->get();

                    foreach ($projects as $project) {
                        foreach ($project->threads as $thread) {
                            $lastRead = $thread->userReads->first()?->last_read_at
                                ?? now()->subYears(10);
                            $sidebarUnreadDiscussion += $thread->messages()
                                ->where('created_at', '>', $lastRead)
                                ->where('user_id', '!=', $user->id)
                                ->count();
                        }
                    }
                } catch (\Exception $e) {
                    $sidebarUnreadDiscussion = 0;
                }

            } else {
                $unreadCount = 0;
                $sidebarUnreadDiscussion = 0;
            }

            $view->with('unreadCount', $unreadCount);
            $view->with('sidebarUnreadDiscussion', $sidebarUnreadDiscussion); // ← BARU
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
}
