<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\ThreadUserRead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ProjectDiscussionService
{
    public const PAGE_SIZE = 30;

    public function __construct(
        private readonly ProjectDiscussionMentionParser $mentionParser,
    ) {}

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
                "{$messagesTable}.id > COALESCE((
                    SELECT {$readsTable}.last_read_message_id
                    FROM {$readsTable}
                    WHERE {$readsTable}.thread_id = {$messagesTable}.project_thread_id
                        AND {$readsTable}.user_id = ?
                    LIMIT 1
                ), 0)",
                [$user->id],
            )
            ->whereRaw(
                "(
                    (
                        SELECT {$readsTable}.last_read_message_id
                        FROM {$readsTable}
                        WHERE {$readsTable}.thread_id = {$messagesTable}.project_thread_id
                            AND {$readsTable}.user_id = ?
                        LIMIT 1
                    ) IS NOT NULL
                    OR {$messagesTable}.created_at > COALESCE((
                        SELECT {$readsTable}.last_read_at
                        FROM {$readsTable}
                        WHERE {$readsTable}.thread_id = {$messagesTable}.project_thread_id
                            AND {$readsTable}.user_id = ?
                        LIMIT 1
                    ), ?)
                )",
                [$user->id, $user->id, '1970-01-01 00:00:00'],
            )
            ->count();
    }

    public function unreadCountForThread(ProjectThread $thread, User $user): int
    {
        $readState = $thread->userReads()
            ->where('user_id', $user->id)
            ->first(['last_read_message_id', 'last_read_at']);

        return $thread->messages()
            ->where('user_id', '!=', $user->id)
            ->when(
                $readState?->last_read_message_id !== null,
                fn (Builder $query) => $query->where('id', '>', $readState->last_read_message_id),
                fn (Builder $query) => $query->when(
                    $readState?->last_read_at !== null,
                    fn (Builder $legacyQuery) => $legacyQuery
                        ->where('created_at', '>', $readState->last_read_at),
                ),
            )
            ->count();
    }

    /**
     * @return array{
     *     messages: Collection<int, ProjectThreadMessage>,
     *     oldest_message_id: ?int,
     *     latest_message_id: ?int,
     *     has_more_older: bool,
     *     has_more_newer: bool
     * }
     */
    public function messagePage(
        ProjectThread $thread,
        ?int $beforeId = null,
        ?int $afterId = null,
    ): array {
        $query = $thread->messages()
            ->select([
                'id',
                'project_thread_id',
                'user_id',
                'content',
                'edited_at',
                'created_at',
                'updated_at',
            ])
            ->with('user:id,name,profile_photo');

        if ($afterId !== null) {
            $fetchedMessages = $query
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(self::PAGE_SIZE + 1)
                ->get();
            $messages = $fetchedMessages->take(self::PAGE_SIZE)->values();

            return $this->messagePagePayload(
                $messages,
                hasMoreOlder: false,
                hasMoreNewer: $fetchedMessages->count() > self::PAGE_SIZE,
            );
        }

        $fetchedMessages = $query
            ->when($beforeId !== null, fn (Builder $messageQuery) => $messageQuery->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();
        $messages = $fetchedMessages
            ->take(self::PAGE_SIZE)
            ->reverse()
            ->values();

        return $this->messagePagePayload(
            $messages,
            hasMoreOlder: $fetchedMessages->count() > self::PAGE_SIZE,
            hasMoreNewer: false,
        );
    }

    /**
     * @return array{
     *     id: int,
     *     content: string,
     *     plain_text: string,
     *     content_segments: list<array{type: 'text'|'mention', text: string, user_id: ?int}>,
     *     sender: array{id: ?int, name: string, avatar: ?string},
     *     created_at: string,
     *     created_at_human: string,
     *     edited_at: ?string,
     *     can_edit: bool,
     *     can_delete: bool
     * }
     */
    public function messagePayload(ProjectThreadMessage $message, User $actor): array
    {
        $message->loadMissing('user:id,name,profile_photo');
        $sender = $message->user;
        $isSender = $sender !== null && (int) $message->user_id === (int) $actor->id;

        return [
            'id' => $message->id,
            'content' => $message->content,
            'plain_text' => $this->mentionParser->plainText($message->content),
            'content_segments' => $this->mentionParser->segments($message->content),
            'sender' => [
                'id' => $sender?->id,
                'name' => $sender?->name ?? 'Deleted user',
                'avatar' => $sender?->profile_photo
                    ? asset('storage/'.$sender->profile_photo)
                    : null,
            ],
            'created_at' => $message->created_at->toIso8601String(),
            'created_at_human' => $message->created_at->format('H:i'),
            'edited_at' => $message->edited_at?->toIso8601String(),
            'can_edit' => $isSender,
            'can_delete' => $isSender,
        ];
    }

    public function createMessage(
        Project $project,
        ProjectThread $thread,
        User $sender,
        string $content,
    ): ProjectThreadMessage {
        return DB::transaction(function () use ($project, $thread, $sender, $content): ProjectThreadMessage {
            $parsedMention = $this->mentionParser->normalize($project, $content);
            $message = $thread->messages()->create([
                'user_id' => $sender->id,
                'content' => $parsedMention['content'],
            ]);
            $message->mentionedUsers()->sync($parsedMention['user_ids']);
            $this->createMentionNotifications(
                $project,
                $thread,
                $message,
                $sender,
                $parsedMention['user_ids'],
            );

            return $message;
        });
    }

    public function updateMessage(
        Project $project,
        ProjectThreadMessage $message,
        User $sender,
        string $content,
    ): ProjectThreadMessage {
        return DB::transaction(function () use ($project, $message, $sender, $content): ProjectThreadMessage {
            $lockedMessage = ProjectThreadMessage::query()
                ->whereKey($message->id)
                ->lockForUpdate()
                ->firstOrFail();
            $thread = $lockedMessage->thread()->firstOrFail();
            $existingMentionIds = $lockedMessage->mentionedUsers()
                ->pluck('users.id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $parsedMention = $this->mentionParser->normalize($project, $content);
            $newMentionIds = array_values(array_diff(
                $parsedMention['user_ids'],
                $existingMentionIds,
            ));

            $lockedMessage->update([
                'content' => $parsedMention['content'],
                'edited_at' => now(),
            ]);
            $lockedMessage->mentionedUsers()->sync($parsedMention['user_ids']);
            $this->createMentionNotifications(
                $project,
                $thread,
                $lockedMessage,
                $sender,
                $newMentionIds,
            );

            return $lockedMessage;
        });
    }

    /**
     * @return list<array{id: int, name: string, avatar: ?string, email_hint: string, role: string}>
     */
    public function mentionCandidates(
        Project $project,
        User $actor,
        string $search,
    ): array {
        return $project->members()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.profile_photo',
            ])
            ->whereKeyNot($actor->id)
            ->where('users.is_active', true)
            ->wherePivotIn('role', [Project::ROLE_MANAGER, Project::ROLE_MEMBER])
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('users.name', 'like', "%{$search}%")
                        ->orWhere('users.email', 'like', "%{$search}%");
                });
            })
            ->orderBy('users.name')
            ->limit(10)
            ->get()
            ->map(function (User $user): array {
                $emailLocal = Str::before($user->email, '@');
                $emailDomain = Str::after($user->email, '@');

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'avatar' => $user->profile_photo
                        ? asset('storage/'.$user->profile_photo)
                        : null,
                    'email_hint' => Str::substr($emailLocal, 0, 1).'***@'.$emailDomain,
                    'role' => Project::roleLabel($user->pivot->role),
                ];
            })
            ->values()
            ->all();
    }

    public function renderedContent(string $content): HtmlString
    {
        return $this->mentionParser->renderedContent($content);
    }

    public function markRead(
        ProjectThread $thread,
        User $user,
        int $messageId,
    ): ThreadUserRead {
        return DB::transaction(function () use ($thread, $user, $messageId): ThreadUserRead {
            $readState = $thread->userReads()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();
            $targetMessage = $thread->messages()->whereKey($messageId)->firstOrFail();

            if ($readState === null) {
                return $thread->userReads()->create([
                    'user_id' => $user->id,
                    'last_read_message_id' => $targetMessage->id,
                    'last_read_at' => $targetMessage->created_at,
                ]);
            }

            if ($readState->last_read_message_id === null
                || $targetMessage->id > $readState->last_read_message_id) {
                $readState->last_read_message_id = $targetMessage->id;
                $readState->last_read_at = $targetMessage->created_at;
                $readState->save();
            }

            return $readState;
        });
    }

    public function deleteMessage(ProjectThreadMessage $message): void
    {
        DB::transaction(function () use ($message): void {
            $previousMessageId = ProjectThreadMessage::query()
                ->where('project_thread_id', $message->project_thread_id)
                ->where('id', '<', $message->id)
                ->max('id');

            ThreadUserRead::query()
                ->where('thread_id', $message->project_thread_id)
                ->where('last_read_message_id', $message->id)
                ->update([
                    'last_read_message_id' => $previousMessageId,
                    'updated_at' => now(),
                ]);

            $message->delete();
        });
    }

    /**
     * @param  list<int>  $mentionedUserIds
     */
    private function createMentionNotifications(
        Project $project,
        ProjectThread $thread,
        ProjectThreadMessage $message,
        User $sender,
        array $mentionedUserIds,
    ): void {
        $targetUserIds = collect($mentionedUserIds)
            ->reject(fn (int $userId): bool => $userId === (int) $sender->id)
            ->values();

        if ($targetUserIds->isEmpty()) {
            return;
        }

        $excerpt = $this->mentionParser->notificationExcerpt($message->content);
        $url = route('discussion.chat', [
            'project' => $project,
            'thread' => $thread,
            'message' => $message->id,
        ], false);

        foreach ($targetUserIds as $targetUserId) {
            Notification::firstOrCreate(
                [
                    'user_id' => $targetUserId,
                    'type' => Notification::TYPE_PROJECT_DISCUSSION_MENTION,
                    'project_thread_message_id' => $message->id,
                ],
                [
                    'title' => $sender->name.' mentioned you in '.$thread->title,
                    'message' => $excerpt,
                    'project_id' => $project->id,
                    'project_thread_id' => $thread->id,
                    'url' => $url,
                    'metadata' => [
                        'project_id' => $project->id,
                        'project_token' => $project->token,
                        'project_name' => $project->name,
                        'thread_id' => $thread->id,
                        'thread_title' => $thread->title,
                        'message_id' => $message->id,
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'excerpt' => $excerpt,
                        'source' => 'project_discussion',
                    ],
                ],
            );
        }
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
                        "{$messagesTable}.id > COALESCE((
                            SELECT {$readsTable}.last_read_message_id
                            FROM {$readsTable}
                            WHERE {$readsTable}.thread_id = {$threadsTable}.id
                                AND {$readsTable}.user_id = ?
                            LIMIT 1
                        ), 0)",
                        [$user->id],
                    )
                    ->whereRaw(
                        "(
                            (
                                SELECT {$readsTable}.last_read_message_id
                                FROM {$readsTable}
                                WHERE {$readsTable}.thread_id = {$threadsTable}.id
                                    AND {$readsTable}.user_id = ?
                                LIMIT 1
                            ) IS NOT NULL
                            OR {$messagesTable}.created_at > COALESCE((
                                SELECT {$readsTable}.last_read_at
                                FROM {$readsTable}
                                WHERE {$readsTable}.thread_id = {$threadsTable}.id
                                    AND {$readsTable}.user_id = ?
                                LIMIT 1
                            ), ?)
                        )",
                        [$user->id, $user->id, '1970-01-01 00:00:00'],
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

    /**
     * @param  Collection<int, ProjectThreadMessage>  $messages
     * @return array{
     *     messages: Collection<int, ProjectThreadMessage>,
     *     oldest_message_id: ?int,
     *     latest_message_id: ?int,
     *     has_more_older: bool,
     *     has_more_newer: bool
     * }
     */
    private function messagePagePayload(
        Collection $messages,
        bool $hasMoreOlder,
        bool $hasMoreNewer,
    ): array {
        return [
            'messages' => $messages,
            'oldest_message_id' => $messages->first()?->id,
            'latest_message_id' => $messages->last()?->id,
            'has_more_older' => $hasMoreOlder,
            'has_more_newer' => $hasMoreNewer,
        ];
    }
}
