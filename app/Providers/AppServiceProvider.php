<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use App\Models\UserNotification;
use App\Models\Project;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('*', function ($view) {

            if (auth()->check()) {

                $unreadCount = UserNotification::where('user_id', auth()->id())
                    ->whereNull('read_at')
                    ->count();

                $user = auth()->user();
                $sidebarUnreadDiscussion = 0;

                try {
                    $projects = Project::where(function ($q) use ($user) {
                        $q->whereHas('members', fn($q) => $q->where('user_id', $user->id))
                            ->orWhere('created_by', $user->id);
                    })
                    ->with(['threads.userReads' => fn($q) => $q->where('user_id', $user->id)])
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
}
