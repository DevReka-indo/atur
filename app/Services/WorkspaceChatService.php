<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChatMessage;
use App\Models\WorkspaceChatRead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class WorkspaceChatService
{
    public const PAGE_SIZE = 30;

    public function __construct(
        private readonly WorkspaceChatMentionParser $mentionParser,
    ) {}

    /**
     * @return array{messages: Collection<int, WorkspaceChatMessage>, has_more: bool}
     */
    public function messages(
        Workspace $workspace,
        ?int $beforeId = null,
        ?int $afterId = null,
    ): array {
        $query = $workspace->chatMessages()
            ->select([
                'id',
                'workspace_id',
                'user_id',
                'content',
                'edited_at',
                'created_at',
                'updated_at',
            ])
            ->with('user:id,name,profile_photo,avatar_url');

        if ($afterId !== null) {
            $messages = $query
                ->where('id', '>', $afterId)
                ->orderBy('id')
                ->limit(self::PAGE_SIZE + 1)
                ->get();

            return [
                'messages' => $messages->take(self::PAGE_SIZE)->values(),
                'has_more' => $messages->count() > self::PAGE_SIZE,
            ];
        }

        $messages = $query
            ->when($beforeId !== null, fn ($messageQuery) => $messageQuery->where('id', '<', $beforeId))
            ->orderByDesc('id')
            ->limit(self::PAGE_SIZE + 1)
            ->get();

        return [
            'messages' => $messages->take(self::PAGE_SIZE)->reverse()->values(),
            'has_more' => $messages->count() > self::PAGE_SIZE,
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     content: string,
     *     plain_text: string,
     *     content_segments: list<array{type: 'text'|'mention', text: string, user_id: ?int}>,
     *     sender: array{id: ?int, name: string, avatar: ?string},
     *     created_at: string,
     *     edited_at: ?string,
     *     can_edit: bool,
     *     can_delete: bool
     * }
     */
    public function messagePayload(WorkspaceChatMessage $message, User $actor): array
    {
        $message->loadMissing('user:id,name,profile_photo,avatar_url');
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
                    : $sender?->avatar_url,
            ],
            'created_at' => $message->created_at->toIso8601String(),
            'edited_at' => $message->edited_at?->toIso8601String(),
            'can_edit' => $isSender,
            'can_delete' => $isSender,
        ];
    }

    public function createMessage(
        Workspace $workspace,
        User $sender,
        string $content,
    ): WorkspaceChatMessage {
        return DB::transaction(function () use ($workspace, $sender, $content): WorkspaceChatMessage {
            $parsedMention = $this->mentionParser->normalize($workspace, $content);
            $message = $workspace->chatMessages()->create([
                'user_id' => $sender->id,
                'content' => $parsedMention['content'],
            ]);
            $message->mentions()->sync($parsedMention['user_ids']);
            $this->createMentionNotifications(
                $workspace,
                $message,
                $sender,
                $parsedMention['user_ids'],
            );

            return $message;
        });
    }

    public function updateMessage(
        WorkspaceChatMessage $message,
        User $sender,
        string $content,
    ): WorkspaceChatMessage {
        return DB::transaction(function () use ($message, $sender, $content): WorkspaceChatMessage {
            $lockedMessage = WorkspaceChatMessage::query()
                ->whereKey($message->id)
                ->lockForUpdate()
                ->firstOrFail();
            $workspace = $lockedMessage->workspace()->firstOrFail();
            $existingMentionIds = $lockedMessage->mentions()
                ->pluck('users.id')
                ->map(fn ($id): int => (int) $id)
                ->all();
            $parsedMention = $this->mentionParser->normalize($workspace, $content);
            $newMentionIds = array_values(array_diff(
                $parsedMention['user_ids'],
                $existingMentionIds,
            ));

            $lockedMessage->update([
                'content' => $parsedMention['content'],
                'edited_at' => now(),
            ]);
            $lockedMessage->mentions()->sync($parsedMention['user_ids']);
            $this->createMentionNotifications(
                $workspace,
                $lockedMessage,
                $sender,
                $newMentionIds,
            );

            return $lockedMessage;
        });
    }

    public function renderedContent(string $content): HtmlString
    {
        return $this->mentionParser->renderedContent($content);
    }

    /**
     * @return list<array{id: int, name: string, avatar: ?string, email_hint: string}>
     */
    public function mentionCandidates(
        Workspace $workspace,
        User $actor,
        string $search,
    ): array {
        return User::query()
            ->select(['id', 'name', 'email', 'profile_photo', 'avatar_url'])
            ->whereKeyNot($actor->id)
            ->where('is_active', true)
            ->where(function ($query) use ($workspace): void {
                $query->whereKey($workspace->created_by)
                    ->orWhereHas(
                        'workspaces',
                        fn ($workspaceQuery) => $workspaceQuery->whereKey($workspace->id),
                    );
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($searchQuery) use ($search): void {
                    $searchQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
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
                        : $user->avatar_url,
                    'email_hint' => Str::substr($emailLocal, 0, 1).'***@'.$emailDomain,
                ];
            })
            ->values()
            ->all();
    }

    public function unreadCount(Workspace $workspace, User $user): int
    {
        $lastReadMessageId = $workspace->chatReads()
            ->where('user_id', $user->id)
            ->value('last_read_message_id') ?? 0;

        return $workspace->chatMessages()
            ->where('id', '>', $lastReadMessageId)
            ->where(function ($query) use ($user): void {
                $query->whereNull('user_id')
                    ->orWhere('user_id', '!=', $user->id);
            })
            ->count();
    }

    public function markRead(
        Workspace $workspace,
        User $user,
        ?int $messageId = null,
    ): WorkspaceChatRead {
        return DB::transaction(function () use ($workspace, $user, $messageId): WorkspaceChatRead {
            $targetMessageId = $messageId ?? $workspace->chatMessages()->max('id');
            $readState = $workspace->chatReads()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if ($readState === null) {
                return $workspace->chatReads()->create([
                    'user_id' => $user->id,
                    'last_read_message_id' => $targetMessageId,
                    'last_read_at' => now(),
                ]);
            }

            if ($targetMessageId !== null
                && ($readState->last_read_message_id === null
                    || $targetMessageId > $readState->last_read_message_id)) {
                $readState->last_read_message_id = $targetMessageId;
            }

            $readState->last_read_at = now();
            $readState->save();

            return $readState;
        });
    }

    public function deleteMessage(WorkspaceChatMessage $message): void
    {
        DB::transaction(function () use ($message): void {
            $previousMessageId = WorkspaceChatMessage::query()
                ->where('workspace_id', $message->workspace_id)
                ->where('id', '<', $message->id)
                ->max('id');

            WorkspaceChatRead::query()
                ->where('workspace_id', $message->workspace_id)
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
        Workspace $workspace,
        WorkspaceChatMessage $message,
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
        $url = route('workspaces.show', [
            'token' => $workspace->token,
            'tab' => 'chat',
            'message' => $message->id,
        ], false);

        foreach ($targetUserIds as $targetUserId) {
            Notification::firstOrCreate(
                [
                    'user_id' => $targetUserId,
                    'type' => Notification::TYPE_WORKSPACE_CHAT_MENTION,
                    'workspace_chat_message_id' => $message->id,
                ],
                [
                    'title' => 'Mention di Workspace Chat',
                    'message' => $sender->name.' menyebut Anda di chat '
                        .$workspace->name.': '.$excerpt,
                    'workspace_id' => $workspace->id,
                    'url' => $url,
                    'metadata' => [
                        'workspace_id' => $workspace->id,
                        'workspace_token' => $workspace->token,
                        'workspace_name' => $workspace->name,
                        'message_id' => $message->id,
                        'sender_id' => $sender->id,
                        'sender_name' => $sender->name,
                        'excerpt' => $excerpt,
                    ],
                ],
            );
        }
    }
}
