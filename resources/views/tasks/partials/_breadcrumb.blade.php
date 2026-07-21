<nav class="flex flex-wrap items-center gap-1.5 text-sm mb-4" aria-label="Task hierarchy breadcrumb">
    <a href="{{ route('projects.show', $task->project->token) }}"
        class="font-medium text-gray-500 transition-colors hover:text-indigo-600">
        {{ $task->project->name }}
    </a>
    @foreach ($hierarchyAncestors as $ancestor)
        <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
        <a href="{{ route('tasks.show', $ancestor->token) }}"
            class="font-medium text-gray-500 transition-colors hover:text-indigo-600">
            {{ $ancestor->name }}
        </a>
    @endforeach
    <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
    <span class="font-semibold text-gray-800">{{ $task->name }}</span>
</nav>
