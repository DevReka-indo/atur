@php
    $lastMessage = $thread->messages->first();
@endphp

<article
    id="thread-{{ $thread->id }}"
    data-thread-id="{{ $thread->id }}"
    class="flex items-center gap-3 rounded-2xl border border-gray-200 bg-white px-5 py-4 shadow-sm transition hover:border-indigo-200 hover:shadow-md"
>
    <a
        href="{{ route('discussion.chat', [$project, $thread]) }}"
        class="flex min-w-0 flex-1 items-center gap-3"
    >
        <div class="flex h-11 w-11 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-50 font-bold text-indigo-700">
            {{ strtoupper(substr($thread->title, 0, 1)) }}
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                <h2 class="truncate text-sm font-semibold text-gray-900">{{ $thread->title }}</h2>
                <span
                    id="badge-{{ $thread->id }}"
                    class="{{ $thread->unread_count > 0 ? '' : 'hidden' }} rounded-full bg-green-500 px-2 py-0.5 text-xs font-bold text-white"
                >
                    {{ $thread->unread_count > 99 ? '99+' : $thread->unread_count }}
                </span>
            </div>
            <p id="preview-{{ $thread->id }}" class="mt-1 truncate text-sm text-gray-500">
                @if ($lastMessage)
                    {{ $lastMessage->user->name ?? 'Unknown' }}: {{ Str::limit($lastMessage->content, 70) }}
                @else
                    No messages yet.
                @endif
            </p>
        </div>

        <div class="hidden flex-shrink-0 text-right sm:block">
            <p id="time-{{ $thread->id }}" class="text-xs text-gray-400">
                {{ $thread->discussion_activity_at?->diffForHumans() }}
            </p>
            <p class="mt-1 text-xs text-gray-400">{{ $thread->messages_count }} messages</p>
        </div>
    </a>

    @if ($canManageDiscussionThreads)
        <div class="flex flex-shrink-0 items-center gap-1">
            <button
                type="button"
                data-discussion-rename
                data-action="{{ route('discussion.threads.update', [$project, $thread]) }}"
                data-title="{{ $thread->title }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-indigo-50 hover:text-indigo-600"
                title="Rename Discussion"
            >
                <i class="fa-solid fa-pencil"></i>
            </button>
            <button
                type="button"
                data-discussion-delete
                data-action="{{ route('discussion.threads.destroy', [$project, $thread]) }}"
                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-600"
                title="Delete Discussion"
            >
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    @endif
</article>
