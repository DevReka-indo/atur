<div class="min-h-0 flex-1 space-y-4 overflow-y-auto px-4 py-5 sm:px-6" data-chat-message-list
    aria-live="polite">
    @include('workspaces.partials.show.chat._message-highlight')
    @include('workspaces.partials.show.chat._empty-state')

    @foreach ($chatMessages as $message)
        @include('workspaces.partials.show.chat._message-item', ['message' => $message])
    @endforeach
</div>
