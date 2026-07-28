<?php

namespace App\Http\Controllers;

use App\Http\Requests\WorkspaceChatMessageRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChatMessage;
use App\Services\WorkspaceChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkspaceChatController extends Controller
{
    public function __construct(
        private readonly WorkspaceChatService $chatService,
    ) {}

    public function index(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        $this->authorizeWorkspaceAccess($workspace, $user);

        $validated = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1', 'prohibits:after_id'],
            'after_id' => ['nullable', 'integer', 'min:1', 'prohibits:before_id'],
        ]);
        $page = $this->chatService->messages(
            $workspace,
            isset($validated['before_id']) ? (int) $validated['before_id'] : null,
            isset($validated['after_id']) ? (int) $validated['after_id'] : null,
        );

        return response()->json([
            'messages' => $page['messages']
                ->map(fn (WorkspaceChatMessage $message): array => $this->chatService
                    ->messagePayload($message, $user))
                ->values(),
            'has_more' => $page['has_more'],
        ]);
    }

    public function store(
        WorkspaceChatMessageRequest $request,
        Workspace $workspace,
    ): JsonResponse {
        $user = $request->user();
        $this->authorizeWorkspaceAccess($workspace, $user);
        $validated = $request->validated();

        $message = $this->chatService->createMessage(
            $workspace,
            $user,
            $validated['content'],
        );

        return response()->json(
            $this->chatService->messagePayload($message, $user),
            201,
        );
    }

    public function update(
        WorkspaceChatMessageRequest $request,
        Workspace $workspace,
        WorkspaceChatMessage $message,
    ): JsonResponse {
        $this->ensureMessageBelongsToWorkspace($workspace, $message);
        $user = $request->user();
        $this->authorizeWorkspaceAccess($workspace, $user);
        abort_unless((int) $message->user_id === (int) $user->id, 403);
        $validated = $request->validated();

        $message = $this->chatService->updateMessage(
            $message,
            $user,
            $validated['content'],
        );

        return response()->json($this->chatService->messagePayload($message, $user));
    }

    public function mentionCandidates(
        Request $request,
        Workspace $workspace,
    ): JsonResponse {
        $user = $request->user();
        $this->authorizeWorkspaceAccess($workspace, $user);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'members' => $this->chatService->mentionCandidates(
                $workspace,
                $user,
                trim($validated['search'] ?? ''),
            ),
        ]);
    }

    public function destroy(
        Request $request,
        Workspace $workspace,
        WorkspaceChatMessage $message,
    ): JsonResponse {
        $this->ensureMessageBelongsToWorkspace($workspace, $message);
        $user = $request->user();
        $this->authorizeWorkspaceAccess($workspace, $user);
        abort_unless((int) $message->user_id === (int) $user->id, 403);

        $this->chatService->deleteMessage($message);

        return response()->json(['success' => true]);
    }

    public function markRead(Request $request, Workspace $workspace): JsonResponse
    {
        $user = $request->user();
        $this->authorizeWorkspaceAccess($workspace, $user);
        $validated = $request->validate([
            'message_id' => ['nullable', 'integer', 'min:1'],
        ]);
        $messageId = isset($validated['message_id'])
            ? (int) $validated['message_id']
            : null;

        if ($messageId !== null) {
            abort_unless(
                $workspace->chatMessages()->whereKey($messageId)->exists(),
                404,
            );
        }

        $readState = $this->chatService->markRead($workspace, $user, $messageId);

        return response()->json([
            'last_read_message_id' => $readState->last_read_message_id,
            'unread_count' => $this->chatService->unreadCount($workspace, $user),
        ]);
    }

    private function authorizeWorkspaceAccess(Workspace $workspace, User $user): void
    {
        abort_unless(
            $user->isSuperAdmin()
                || $workspace->isOwner($user)
                || $workspace->isMember($user),
            403,
        );
    }

    private function ensureMessageBelongsToWorkspace(
        Workspace $workspace,
        WorkspaceChatMessage $message,
    ): void {
        abort_unless((int) $message->workspace_id === (int) $workspace->id, 404);
    }
}
