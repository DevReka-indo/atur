<div id="discussion-list" class="space-y-3">
    @forelse ($threads as $thread)
        @include('discussion.partials.project._thread-item', [
            'thread' => $thread,
            'canManageDiscussionThreads' => $canManageDiscussionThreads,
        ])
    @empty
        @include('discussion.partials.project._empty-state')
    @endforelse
</div>
