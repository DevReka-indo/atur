<?php

namespace App\Services;

use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\ThreadUserRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProjectDiscussionService
{
    /**
     * @return Collection<int, Project>
     */
    public function projectsForHub(
        User $user,
        ?string $search = null,
        bool $unreadOnly = false,
    ): Collection {
        $projects = Project::query()
            ->when(
                ! $user->isSuperAdmin(),
                fn (Builder $query) => $query->whereHas(
                    'projectMembers',
                    fn (Builder $memberQuery) => $memberQuery
                        ->where('user_id', $user->id)
                        ->whereIn('role', [Project::ROLE_MANAGER, Project::ROLE_MEMBER]),
                ),
            )
            ->when(
                filled($search),
                fn (Builder $query) => $query->where(function (Builder $searchQuery) use ($search): void {
                    $searchQuery
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas(
                            'threads',
                            fn (Builder $threadQuery) => $threadQuery->where('title', 'like', '%'.$search.'%'),
                        );
                }),
            )
            ->with('workspace:id,name')
            ->withCount('threads')
            ->with([
                'threads' => fn (HasMany $query) => $this->threadSummaryQuery($query, $user),
            ])
            ->latest()
            ->get()
            ->each(function (Project $project): void {
                $project->setAttribute('unread_total', $project->threads->sum('unread_count'));
                $latestThread = $project->threads->sortByDesc('discussion_activity_at')->first();
                $project->setRelation('latestDiscussionThread', $latestThread);
                $project->setAttribute(
                    'discussion_activity_at',
                    $latestThread?->getAttribute('discussion_activity_at') ?? $project->created_at,
                );
            })
            ->when($unreadOnly, fn (Collection $projects) => $projects->where('unread_total', '>', 0))
            ->sortByDesc('discussion_activity_at')
            ->values();

        return $projects;
    }

    /**
     * @return Collection<int, ProjectThread>
     */
    public function threadsForProject(Project $project, User $user): Collection
    {
        return $this->threadSummaryQuery($project->threads(), $user)
            ->get()
            ->each(function (ProjectThread $thread): void {
                $thread->setAttribute(
                    'discussion_activity_at',
                    $thread->messages->first()?->created_at ?? $thread->created_at,
                );
            })
            ->sortByDesc(function (ProjectThread $thread): array {
                return [
                    $thread->unread_count > 0 ? 1 : 0,
                    $thread->discussion_activity_at?->timestamp ?? 0,
                ];
            })
            ->values();
    }

    public function unreadTotalForUser(User $user): int
    {
        $messagesTable = (new ProjectThreadMessage)->getTable();
        $readsTable = (new ThreadUserRead)->getTable();
        $threadsTable = (new ProjectThread)->getTable();

        return ProjectThreadMessage::query()
            ->where($messagesTable.'.user_id', '!=', $user->id)
            ->whereHas('thread.project', function (Builder $query) use ($user): void {
                if (! $user->isSuperAdmin()) {
                    $query->whereHas(
                        'projectMembers',
                        fn (Builder $memberQuery) => $memberQuery
                            ->where('user_id', $user->id)
                            ->whereIn('role', [Project::ROLE_MANAGER, Project::ROLE_MEMBER]),
                    );
                }
            })
            ->whereRaw(
                "{$messagesTable}.created_at > COALESCE((
                    SELECT {$readsTable}.last_read_at
                    FROM {$readsTable}
                    WHERE {$readsTable}.thread_id = {$messagesTable}.project_thread_id
                        AND {$readsTable}.user_id = ?
                    LIMIT 1
                ), ?)",
                [$user->id, now()->subYears(10)],
            )
            ->count();
    }

    private function threadSummaryQuery(Builder|HasMany $query, User $user): Builder|HasMany
    {
        $messagesTable = (new ProjectThreadMessage)->getTable();
        $readsTable = (new ThreadUserRead)->getTable();
        $threadsTable = (new ProjectThread)->getTable();

        return $query
            ->withCount('messages')
            ->withCount([
                'messages as unread_count' => fn (Builder $messageQuery) => $messageQuery
                    ->where($messagesTable.'.user_id', '!=', $user->id)
                    ->whereRaw(
                        "{$messagesTable}.created_at > COALESCE((
                            SELECT {$readsTable}.last_read_at
                            FROM {$readsTable}
                            WHERE {$readsTable}.thread_id = {$threadsTable}.id
                                AND {$readsTable}.user_id = ?
                            LIMIT 1
                        ), ?)",
                        [$user->id, now()->subYears(10)],
                    ),
            ])
            ->with([
                'creator:id,name',
                'messages' => fn (HasMany $messageQuery) => $messageQuery
                    ->with('user:id,name')
                    ->latest()
                    ->limit(1),
            ]);
    }
}
