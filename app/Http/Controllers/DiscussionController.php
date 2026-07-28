<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\ThreadUserRead;
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

        ThreadUserRead::updateOrCreate(
            ['thread_id' => $thread->id, 'user_id' => $request->user()->id],
            ['last_read_at' => now()]
        );

        $messages = $thread->messages()->with('user')->oldest()->get();

        return view('discussion.chat', compact('project', 'thread', 'messages'));
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

        $validated = $request->validate(['content' => 'required|string|max:1000']);

        $message = $thread->messages()->create([
            'user_id' => $request->user()->id,
            'content' => $validated['content'],
        ]);

        $message->load('user');

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
            'time' => $message->created_at->format('H:i'),
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
            ],
        ]);
    }

    // Edit pesan
    public function updateMessage(Request $request, Project $project, ProjectThread $thread, ProjectThreadMessage $message): JsonResponse
    {
        abort_unless($project->canPostDiscussionMessages($request->user()), 403);
        abort_unless($message->user_id === $request->user()->id, 403);

        $validated = $request->validate(['content' => 'required|string|max:1000']);

        $message->update([
            'content' => $validated['content'],
        ]);

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
        ]);
    }

    // Hapus pesan
    public function destroyMessage(Request $request, Project $project, ProjectThread $thread, ProjectThreadMessage $message): JsonResponse
    {
        abort_unless($project->canPostDiscussionMessages($request->user()), 403);
        abort_unless($message->user_id === $request->user()->id, 403);

        $message->delete();

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
}
