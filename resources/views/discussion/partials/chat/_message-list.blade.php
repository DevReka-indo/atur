<main id="discussion-message-list" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
    @forelse ($messages as $message)
        @include('discussion.partials.chat._message-item', ['message' => $message])
    @empty
        <div id="discussion-empty-state" class="flex h-full min-h-64 flex-col items-center justify-center text-center">
            <i class="fa-regular fa-comment-dots text-4xl text-gray-300"></i>
            <h2 class="mt-3 text-sm font-semibold text-gray-600">No messages yet</h2>
            <p class="mt-1 text-xs text-gray-400">Start this discussion with the first message.</p>
        </div>
    @endforelse
</main>
