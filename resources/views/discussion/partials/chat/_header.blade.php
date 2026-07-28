<header class="flex flex-shrink-0 items-center gap-3 border-b border-gray-200 bg-white px-4 py-3 shadow-sm">
    <a
        href="{{ route('discussion.show', $project) }}"
        class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
        title="Back to Discussion Threads"
    >
        <i class="fa-solid fa-arrow-left"></i>
    </a>

    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full font-bold {{ $project->getInitialColor() }}">
        {{ strtoupper(substr($project->name, 0, 1)) }}
    </div>

    <div class="min-w-0 flex-1">
        <h1 class="truncate text-sm font-semibold text-gray-900">{{ $thread->title }}</h1>
        <p class="truncate text-xs text-gray-500">{{ $project->name }} · Project Discussions</p>
    </div>

    <a href="{{ route('discussion.index') }}" class="hidden text-xs font-medium text-indigo-600 hover:text-indigo-700 sm:inline">
        Project Discussions hub
    </a>
    <button
        type="button"
        id="discussion-search-toggle"
        class="flex h-9 w-9 items-center justify-center rounded-full text-gray-500 hover:bg-gray-100"
        title="Search messages"
    >
        <i class="fa-solid fa-magnifying-glass"></i>
    </button>
</header>

<div id="discussion-search" class="hidden flex-shrink-0 border-b border-gray-200 bg-white px-4 py-2">
    <label class="flex items-center gap-2">
        <i class="fa-solid fa-magnifying-glass text-sm text-gray-400"></i>
        <input
            id="discussion-search-input"
            type="search"
            placeholder="Search messages..."
            class="min-w-0 flex-1 border-0 bg-transparent text-sm focus:ring-0"
        >
        <span id="discussion-search-count" class="text-xs text-gray-400"></span>
    </label>
</div>
