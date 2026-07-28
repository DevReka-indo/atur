<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Project;
use App\Models\ProjectThread;
use App\Models\ProjectThreadMessage;
use App\Models\ThreadUserRead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscussionController extends Controller
{
    // List semua project
    public function index()
    {
        $user = Auth::user();

        $projects = Project::withCount('threads')
            ->where(function ($q) use ($user) {
                $q->whereHas('members', fn ($q) => $q->where('user_id', $user->id))
                    ->orWhere('created_by', $user->id);
            })
            ->with(['threads.userReads' => fn ($q) => $q->where('user_id', $user->id)])
            ->get()
            ->map(function ($project) use ($user) {
                $project->unread_total = 0;
                foreach ($project->threads as $thread) {
                    $lastRead = $thread->userReads->first()?->last_read_at
                        ?? now()->subYears(10);
                    $project->unread_total += $thread->messages()
                        ->where('created_at', '>', $lastRead)
                        ->where('user_id', '!=', $user->id)
                        ->count();
                }

                return $project;
            })
            ->sortByDesc(function ($project) {
                if ($project->unread_total > 0) {
                    return now()->timestamp + $project->unread_total;
                }

                return $project->created_at->timestamp;
            })
            ->values();

        return view('discussion.index', compact('projects'));
    }

    // List threads/topics per project
    public function show(Project $project)
    {
        abort_unless($project->isMember(Auth::user()), 403);

        $user = Auth::user(); // ← tambah ini

        $threads = $project->threads()
            ->withCount('messages')
            ->with([
                'creator',
                'messages' => fn ($q) => $q->with('user')->latest()->limit(1),
                'userReads' => fn ($q) => $q->where('user_id', $user->id), // ← tambah ini
            ])
            ->get()
            ->map(function ($thread) use ($user) {
                $lastRead = $thread->userReads->first()?->last_read_at ?? now()->subYears(10);
                $thread->unread_count = $thread->messages()
                    ->where('created_at', '>', $lastRead)
                    ->where('user_id', '!=', $user->id)
                    ->count();

                return $thread;
            })
            ->sortByDesc(function ($thread) {
                if ($thread->unread_count > 0) {
                    return now()->timestamp + $thread->unread_count;
                }
                $lastMsg = $thread->messages->first();

                return $lastMsg ? $lastMsg->created_at->timestamp : $thread->created_at->timestamp;
            })
            ->values();

        return view('discussion.show', compact('project', 'threads'));
    }

    // Chat messages per thread
    public function chat(Project $project, ProjectThread $thread)
    {
        abort_unless($project->isMember(Auth::user()), 403);

        // ← tambah ini: tandai sudah dibaca
        ThreadUserRead::updateOrCreate(
            ['thread_id' => $thread->id, 'user_id' => Auth::id()],
            ['last_read_at' => now()]
        );

        $messages = $thread->messages()->with('user')->oldest()->get();

        return view('discussion.chat', compact('project', 'thread', 'messages'));
    }

    // Buat thread baru
    public function storeThread(Request $request, Project $project)
    {
        abort_unless($project->canCreateThread(Auth::user()), 403);

        $request->validate(['name' => 'required|string|max:255']);

        $thread = $project->threads()->create([
            'user_id' => Auth::id(),
            'title' => $request->name,
        ]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'created',
            'entity_type' => 'discussion',
            'entity_id' => $thread->id,
            'description' => 'Membuat topik diskusi: '.$thread->title.' di project '.$project->name,
        ]);

        return redirect()->route('discussion.show', $project);
    }

    // Kirim pesan
    public function storeMessage(Request $request, Project $project, ProjectThread $thread)
    {
        abort_unless($project->isMember(Auth::user()), 403);

        $request->validate(['content' => 'required|string|max:1000']);

        $message = $thread->messages()->create([
            'user_id' => Auth::id(),
            'content' => $request->content,
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
    public function updateMessage(Request $request, Project $project, ProjectThread $thread, ProjectThreadMessage $message)
    {
        abort_unless($project->isMember(Auth::user()), 403);
        abort_unless($message->user_id === Auth::id(), 403);

        $request->validate(['content' => 'required|string|max:1000']);

        $message->update([
            'content' => $request->content,
        ]);

        return response()->json([
            'id' => $message->id,
            'content' => $message->content,
        ]);
    }

    // Hapus pesan
    public function destroyMessage(Project $project, ProjectThread $thread, ProjectThreadMessage $message)
    {
        abort_unless($project->isMember(Auth::user()), 403);
        abort_unless($message->user_id === Auth::id(), 403);

        $message->delete();

        return response()->json(['success' => true]);
    }

    public function unreadCounts(Project $project)
    {
        abort_unless($project->isMember(Auth::user()), 403);

        $user = Auth::user();

        $threads = $project->threads()
            ->with([
                'userReads' => fn ($q) => $q->where('user_id', $user->id),
                'messages' => fn ($q) => $q->with('user')->latest()->limit(1),
            ])
            ->get()
            ->map(function ($thread) use ($user) {
                $lastRead = $thread->userReads->first()?->last_read_at ?? now()->subYears(10);
                $lastMsg = $thread->messages->first();

                return [
                    'id' => $thread->id,
                    'unread_count' => $thread->messages()
                        ->where('created_at', '>', $lastRead)
                        ->where('user_id', '!=', $user->id)
                        ->count(),
                    'last_message' => $lastMsg ? [
                        'content' => \Str::limit($lastMsg->content, 70),
                        'user_name' => $lastMsg->user->name ?? 'Unknown',
                        'created_at' => $lastMsg->created_at,
                        'time' => $lastMsg->created_at->isToday()
                            ? $lastMsg->created_at->format('H:i')
                            : ($lastMsg->created_at->isYesterday()
                                ? 'Yesterday'
                                : $lastMsg->created_at->format('d M Y')),
                    ] : null,
                ];
            });

        return response()->json($threads);
    }

    // notifikasi di sidebar
    public function unreadSidebar()
    {
        $user = Auth::user();
        $count = 0;

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
                $count += $thread->messages()
                    ->where('created_at', '>', $lastRead)
                    ->where('user_id', '!=', $user->id)
                    ->count();
            }
        }

        return response()->json(['count' => $count]);
    }

    public function destroyThread(Project $project, ProjectThread $thread)
    {
        abort_unless($project->isMember(Auth::user()), 403);

        $userProjectRole = $project->roleForUser(Auth::user());
        $isPrivileged = $project->created_by === Auth::id()
            || in_array($userProjectRole, ['owner', 'manager', 'member'])
            || $project->workspace->isAdmin(Auth::user());

        abort_unless($isPrivileged || $thread->user_id === Auth::id(), 403);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'entity_type' => 'discussion',
            'entity_id' => $thread->id,
            'description' => 'Menghapus topik diskusi: '.$thread->title.' di project '.$project->name,
        ]);

        $thread->messages()->delete();
        $thread->delete();

        return redirect()->route('discussion.show', $project)
            ->with('success', 'Topik berhasil dihapus.');
    }

    public function updateThread(Request $request, Project $project, ProjectThread $thread)
    {
        abort_unless($project->isMember(Auth::user()), 403);

        $userProjectRole = $project->roleForUser(Auth::user());
        $isPrivileged = $project->created_by === Auth::id()
            || in_array($userProjectRole, ['owner', 'manager', 'member'])
            || $project->workspace->isAdmin(Auth::user());

        abort_unless($isPrivileged || $thread->user_id === Auth::id(), 403);

        $request->validate(['name' => 'required|string|max:255']);
        $thread->update(['title' => $request->name]);

        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => 'updated',
            'entity_type' => 'discussion',
            'entity_id' => $thread->id,
            'description' => 'Mengubah topik diskusi: '.$thread->title.' di project '.$project->name,
        ]);

        return redirect()->route('discussion.show', $project)
            ->with('success', 'Topik berhasil diperbarui.');
    }
}
