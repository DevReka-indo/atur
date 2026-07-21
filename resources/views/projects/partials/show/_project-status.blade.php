<div class="relative" data-dropdown="project-status">
    @php
        $currentStatusClass =
            $projectStatusColors[$project->status]
            ?? 'bg-gray-100 text-gray-700';
    @endphp

    @if ($canChangeProjectStatus)
        <button
            type="button"
            onclick="toggleProjectDropdown('project-status')"
            class="inline-flex cursor-pointer items-center gap-1.5 rounded-full px-3 py-1.5
                text-sm font-medium transition hover:opacity-80 {{ $currentStatusClass }}"
        >
            {{ $projectStatuses[$project->status] ?? str($project->status)->replace('_', ' ')->title() }}

            <i class="fa-solid fa-chevron-down text-xs opacity-70"></i>
        </button>

        <div
            id="dropdown-project-status"
            class="project-dropdown-menu absolute z-50 mt-2 hidden w-44 overflow-hidden
                rounded-xl border border-gray-200 bg-white py-1 shadow-lg"
        >
            @foreach ($projectStatuses as $statusValue => $statusLabel)
                <form
                    method="POST"
                    action="{{ route('projects.updateStatus', $project->token) }}"
                >
                    @csrf
                    @method('PATCH')

                    <input
                        type="hidden"
                        name="status"
                        value="{{ $statusValue }}"
                    >

                    <button
                        type="submit"
                        class="flex w-full items-center gap-2 px-4 py-2 text-left text-sm
                            transition-colors
                            {{ $project->status === $statusValue
                                ? 'bg-gray-100 font-semibold text-gray-900'
                                : 'text-gray-600 hover:bg-gray-50' }}"
                    >
                        @if ($project->status === $statusValue)
                            <i class="fa-solid fa-check w-3 text-xs text-green-500"></i>
                        @else
                            <span class="w-3"></span>
                        @endif

                        {{ $statusLabel }}
                    </button>
                </form>
            @endforeach
        </div>
    @else
        <span
            class="inline-flex items-center justify-center rounded-full px-3 py-1.5
                text-sm font-medium {{ $currentStatusClass }}"
            title="Viewer tidak dapat mengubah status project"
        >
            {{ $projectStatuses[$project->status] ?? str($project->status)->replace('_', ' ')->title() }}
        </span>
    @endif
</div>
