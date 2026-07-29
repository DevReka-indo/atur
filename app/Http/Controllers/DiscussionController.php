<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Services\ProjectDiscussionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DiscussionController extends Controller
{
    public function __construct(
        private ProjectDiscussionService $projectDiscussionService,
    ) {}

    public function index(Request $request): View
    {
        $projects = $this->projectDiscussionService->projectsForHub(
            $request->user(),
            $request->string('search')->trim()->toString() ?: null,
            $request->boolean('unread'),
        );

        return view('discussion.index', [
            'projects' => $projects,
            'search' => $request->string('search')->toString(),
            'unreadOnly' => $request->boolean('unread'),
        ]);
    }

    public function show(Request $request, Project $project): View
    {
        abort_unless($project->canViewDiscussions($request->user()), 403);

        return view('discussion.show', [
            'project' => $project->loadMissing('workspace:id,name'),
            'threads' => $this->projectDiscussionService->threadsForProject($project, $request->user()),
            'discussionContext' => 'hub',
        ]);
    }

    public function chat(Request $request, Project $project, ProjectThread $thread): View
    {
        abort_unless($project->canViewDiscussions($request->user()), 403);

        $requestedMessageId = $request->integer('message');
        $targetMessageId = $requestedMessageId > 0
            && $thread->messages()->whereKey($requestedMessageId)->exists()
                ? $requestedMessageId
                : null;
        $targetMessageMissing = $request->has('message')
            && $targetMessageId === null;
        $page = $targetMessageId !== null
            ? $this->projectDiscussionService->messagePage($thread, $targetMessageId + 1)
            : $this->projectDiscussionService->messagePage($thread);
        $messages = $page['messages']
            ->map(function (ProjectThreadMessage $message) use ($request): array {
                $payload = $this->projectDiscussionService
                    ->messagePayload($message, $request->user());
                $payload['rendered_content'] = $this->projectDiscussionService
                    ->renderedContent($message->content);

                return $payload;
            });

        if ($page['latest_message_id'] !== null && ! $targetMessageMissing) {
            $this->projectDiscussionService->markRead(
                $thread,
                $request->user(),
                $page['latest_message_id'],
            );
        }

        return view('discussion.chat', [
            'project' => $project,
            'thread' => $thread,
            'messages' => $messages,
            'oldestMessageId' => $page['oldest_message_id'],
            'latestMessageId' => $page['latest_message_id'],
            'hasMoreOlder' => $page['has_more_older'],
            'targetMessageId' => $targetMessageId,
            'targetMessageMissing' => $targetMessageMissing,
        ]);
    }

    public function messages(Request $request, Project $project, ProjectThread $thread): JsonResponse
    {
        abort_unless($project->canViewDiscussions($request->user()), 403);

        $validated = $request->validate([
            'before_id' => ['nullable', 'integer', 'min:1', 'prohibits:after_id'],
            'after_id' => ['nullable', 'integer', 'min:0', 'prohibits:before_id'],
        ]);
        $page = $this->projectDiscussionService->messagePage(
            $thread,
            isset($validated['before_id']) ? (int) $validated['before_id'] : null,
            isset($validated['after_id']) ? (int) $validated['after_id'] : null,
        );

        return response()->json([
            'messages' => $page['messages']
                ->map(fn (ProjectThreadMessage $message): array => $this->projectDiscussionService
                    ->messagePayload($message, $request->user()))
                ->values(),
            'oldest_message_id' => $page['oldest_message_id'],
            'latest_message_id' => $page['latest_message_id'],
            'has_more_older' => $page['has_more_older'],
            'has_more_newer' => $page['has_more_newer'],
        ]);
    }

    public function markThreadRead(Request $request, Project $project, ProjectThread $thread): JsonResponse
    {
        abort_unless($project->canViewDiscussions($request->user()), 403);

        $validated = $request->validate([
            'last_read_message_id' => ['required', 'integer', 'min:1'],
        ]);
        $readState = $this->projectDiscussionService->markRead(
            $thread,
            $request->user(),
            (int) $validated['last_read_message_id'],
        );

        return response()->json([
            'last_read_message_id' => $readState->last_read_message_id,
            'unread_count' => $this->projectDiscussionService->unreadCountForThread(
                $thread,
                $request->user(),
            ),
        ]);
    }

    public function mentionCandidates(
        Request $request,
        Project $project,
        ProjectThread $thread,
    ): JsonResponse {
        abort_unless($project->canViewDiscussions($request->user()), 403);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return response()->json([
            'members' => $this->projectDiscussionService->mentionCandidates(
                $project,
                $request->user(),
                trim($validated['search'] ?? ''),
            ),
        ]);
    }

    // Buat thread baru
    public function storeThread(Request $request, Project $project): RedirectResponse
    {
        abort_unless($project->canManageDiscussionThreads($request->user()), 403);

        $validated = $request->validate(['name' => 'required|string|max:255']);

        $thread = $project->threads()->create([
            'user_id' => $request->user()->id,
            'title' => $validated['name'],
        ]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'created',
            'entity_type' => 'discussion',
            'entity_id' => $thread->id,
            'description' => 'Membuat Project Discussion: '.$thread->title.' di project '.$project->name,
        ]);

        return $this->redirectAfterThreadAction($request, $project);
    }

    // Kirim pesan
    public function storeMessage(Request $request, Project $project, ProjectThread $thread): JsonResponse
    {
        abort_unless($project->canPostDiscussionMessages($request->user()), 403);

        $validated = $this->validateMessageContent($request);

        $message = $this->projectDiscussionService->createMessage(
            $project,
            $thread,
            $request->user(),
            $validated['content'],
        );

        return response()->json(
            $this->projectDiscussionService->messagePayload($message, $request->user()),
        );
    }

    // Edit pesan
    public function updateMessage(Request $request, Project $project, ProjectThread $thread, ProjectThreadMessage $message): JsonResponse
    {
        abort_unless($project->canPostDiscussionMessages($request->user()), 403);
        abort_unless($message->user_id === $request->user()->id, 403);

        $validated = $this->validateMessageContent($request);

        $message = $this->projectDiscussionService->updateMessage(
            $project,
            $message,
            $request->user(),
            $validated['content'],
        );

        return response()->json(
            $this->projectDiscussionService->messagePayload($message, $request->user()),
        );
    }

    // Hapus pesan
    public function destroyMessage(Request $request, Project $project, ProjectThread $thread, ProjectThreadMessage $message): JsonResponse
    {
        abort_unless($project->canPostDiscussionMessages($request->user()), 403);
        abort_unless($message->user_id === $request->user()->id, 403);

        $this->projectDiscussionService->deleteMessage($message);

        return response()->json(['success' => true]);
    }

    public function unreadCounts(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->canViewDiscussions($request->user()), 403);

        $threads = $this->projectDiscussionService
            ->threadsForProject($project, $request->user())
            ->map(function (ProjectThread $thread): array {
                $lastMessage = $thread->messages->first();

                return [
                    'id' => $thread->id,
                    'unread_count' => $thread->unread_count,
                    'last_message' => $lastMessage ? [
                        'content' => \Str::limit($lastMessage->content, 70),
                        'user_name' => $lastMessage->user->name ?? 'Unknown',
                        'created_at' => $lastMessage->created_at,
                        'time' => $lastMessage->created_at->isToday()
                            ? $lastMessage->created_at->format('H:i')
                            : ($lastMessage->created_at->isYesterday()
                                ? 'Yesterday'
                                : $lastMessage->created_at->format('d M Y')),
                    ] : null,
                ];
            });

        return response()->json($threads);
    }

    public function unreadSidebar(Request $request): JsonResponse
    {
        return response()->json([
            'count' => $this->projectDiscussionService->unreadTotalForUser($request->user()),
        ]);
    }

    public function destroyThread(Request $request, Project $project, ProjectThread $thread): RedirectResponse
    {
        abort_unless($project->canManageDiscussionThreads($request->user()), 403);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'deleted',
            'entity_type' => 'discussion',
            'entity_id' => $thread->id,
            'description' => 'Menghapus Project Discussion: '.$thread->title.' di project '.$project->name,
        ]);

        $thread->messages()->delete();
        $thread->delete();

        return $this->redirectAfterThreadAction($request, $project)
            ->with('success', 'Discussion berhasil dihapus.');
    }

    public function updateThread(Request $request, Project $project, ProjectThread $thread): RedirectResponse
    {
        abort_unless($project->canManageDiscussionThreads($request->user()), 403);

        $validated = $request->validate(['name' => 'required|string|max:255']);
        $thread->update(['title' => $validated['name']]);

        ActivityLog::create([
            'user_id' => $request->user()->id,
            'action' => 'updated',
            'entity_type' => 'discussion',
            'entity_id' => $thread->id,
            'description' => 'Mengubah Project Discussion: '.$thread->title.' di project '.$project->name,
        ]);

        return $this->redirectAfterThreadAction($request, $project)
            ->with('success', 'Discussion berhasil diperbarui.');
    }

    private function redirectAfterThreadAction(Request $request, Project $project): RedirectResponse
    {
        if ($request->string('return_to')->toString() === 'project') {
            return redirect()->route('projects.show', [
                'token' => $project->token,
                'tab' => 'discussions',
            ]);
        }

        return redirect()->route('discussion.show', $project);
    }

    /**
     * @return array{content: string}
     */
    private function validateMessageContent(Request $request): array
    {
        if (is_string($request->input('content'))) {
            $request->merge(['content' => trim($request->input('content'))]);
        }

        return $request->validate([
            'content' => ['required', 'string', 'max:1000'],
        ]);
    }
}
