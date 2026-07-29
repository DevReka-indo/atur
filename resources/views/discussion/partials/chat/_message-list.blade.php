<main id="discussion-message-scroll" class="relative flex-1 overflow-y-auto px-4 py-4">
    @include('discussion.partials.chat._target-message-state')
    @include('discussion.partials.chat._load-older')

    <div id="discussion-message-list" class="space-y-3">
        @forelse ($messages as $message)
            @include('discussion.partials.chat._message-item', ['message' => $message])
        @empty
            @include('discussion.partials.chat._empty-state')
        @endforelse
    </div>

    @include('discussion.partials.chat._new-messages-indicator')
</main>
