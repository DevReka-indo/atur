<div class="mx-auto mb-8 max-w-8xl">
    <nav class="mb-4 flex items-center gap-2 text-sm text-gray-500">
        <a
            href="{{ route('workspaces.show', $project->workspace->token) }}"
            class="transition-colors hover:text-indigo-600 hover:underline"
        >
            {{ $project->workspace->name }}
        </a>

        <span class="text-gray-400">/</span>

        <span class="font-medium text-gray-900">
            {{ $project->name }}
        </span>
    </nav>

    <div class="mb-6 flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
        <div class="min-w-0 flex-1">
            <h1 class="mb-3 truncate text-3xl font-bold text-gray-900">
                {{ $project->name }}
            </h1>

            <div class="flex flex-wrap items-center gap-4">
                @include('projects.partials.show._project-status')

                <span class="flex items-center gap-2 text-sm text-gray-600">
                    <i class="fa-regular fa-calendar text-gray-400"></i>

                    {{ $project->start_date?->format('d M Y') ?? '—' }}

                    <span class="text-gray-300">—</span>

                    {{ $project->end_date?->format('d M Y') ?? '—' }}
                </span>
            </div>
        </div>

        @if ($isManager)
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('projects.edit', $project->token) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5
                        text-sm font-medium text-white transition-colors hover:bg-blue-700"
                >
                    <i class="fa-solid fa-pen"></i>
                    Edit
                </a>

                <button
                    type="button"
                    onclick="openProjectModal('delete-project-modal')"
                    class="inline-flex items-center gap-2 rounded-xl bg-red-600 px-4 py-2.5
                        text-sm font-medium text-white transition-colors hover:bg-red-700"
                >
                    <i class="fa-regular fa-trash-can"></i>
                    Delete
                </button>
            </div>
        @endif
    </div>

    @include('projects.partials.show._progress')
</div>
