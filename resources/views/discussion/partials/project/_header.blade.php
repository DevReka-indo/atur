<header class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
    <div>
        @if (($discussionContext ?? 'hub') === 'hub')
            <nav class="mb-4 flex flex-wrap items-center gap-2 text-sm text-gray-500" aria-label="Breadcrumb">
                <a href="{{ route('discussion.index') }}" class="hover:text-indigo-600">Project Discussions</a>
                <i class="fa-solid fa-chevron-right text-xs"></i>
                <a href="{{ route('projects.show', $project->token) }}" class="hover:text-indigo-600">{{ $project->name }}</a>
            </nav>
        @endif

        <h1 class="text-2xl font-bold text-gray-900">Project Discussions</h1>
        <p class="mt-1 text-sm text-gray-500">
            {{ $project->name }} · {{ $threads->count() }} discussion threads
        </p>
    </div>

    @if ($canManageDiscussionThreads)
        <button
            type="button"
            data-discussion-modal-open="create-discussion-modal"
            class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-700"
        >
            <i class="fa-solid fa-plus"></i>
            New Discussion
        </button>
    @endif
</header>
