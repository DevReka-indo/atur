@php
    $latestThread = $project->getRelation('latestDiscussionThread');
    $latestMessage = $latestThread?->messages?->first();
@endphp

<article class="flex h-full flex-col rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:shadow-lg">
    <div class="flex items-start gap-4">
        <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl text-xl font-semibold {{ $project->getInitialColor() }}">
            {{ strtoupper(substr($project->name, 0, 1)) }}
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h2 class="truncate text-lg font-semibold text-gray-900">{{ $project->name }}</h2>
                @if ($project->unread_total > 0)
                    <span class="h-2 w-2 flex-shrink-0 rounded-full bg-red-500"></span>
                @endif
            </div>
            <p class="mt-1 truncate text-sm text-gray-500">
                <i class="fa-solid fa-layer-group mr-1"></i>
                {{ $project->workspace->name ?? 'Workspace' }}
            </p>
        </div>
    </div>

    <div class="mt-5 flex-1 rounded-xl bg-gray-50 p-3">
        @if ($latestThread)
            <p class="truncate text-sm font-semibold text-gray-800">{{ $latestThread->title }}</p>
            <p class="mt-1 truncate text-xs text-gray-500">
                @if ($latestMessage)
                    {{ $latestMessage->user->name ?? 'Unknown' }}:
                    {{ Str::limit($latestMessage->content, 80) }}
                @else
                    No messages yet.
                @endif
            </p>
            <p class="mt-2 text-xs text-gray-400">
                {{ $project->discussion_activity_at?->diffForHumans() }}
            </p>
        @else
            <p class="text-sm text-gray-500">No discussion thread yet.</p>
        @endif
    </div>

    <div class="mt-4 flex items-center justify-between gap-3">
        <div class="flex items-center gap-2 text-xs text-gray-500">
            <span>
                <i class="fa-regular fa-comments mr-1"></i>
                {{ $project->threads_count }} threads
            </span>
            @if ($project->unread_total > 0)
                <span class="rounded-full bg-red-50 px-2 py-1 font-semibold text-red-600">
                    {{ $project->unread_total }} unread
                </span>
            @endif
        </div>

        <a
            href="{{ route('discussion.show', $project) }}"
            class="inline-flex items-center gap-2 whitespace-nowrap text-sm font-semibold text-indigo-600 hover:text-indigo-700"
        >
            Open Discussions
            <i class="fa-solid fa-arrow-right"></i>
        </a>
    </div>
</article>
