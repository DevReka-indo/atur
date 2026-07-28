<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChatMessage;
use App\Models\WorkspaceChatRead;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class WorkspaceChatService
{
    public const PAGE_SIZE = 30;

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
}
