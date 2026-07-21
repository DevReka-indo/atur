<div class="relative group">
    <button
        type="button"
        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border border-gray-200
            bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm
            transition-all hover:bg-gray-50"
    >
        @if ($currentView === 'gantt')
            <i class="fa-solid fa-chart-gantt text-indigo-500"></i>
            Gantt
        @elseif ($currentView === 'kanban')
            <i class="fa-solid fa-table-columns text-indigo-500"></i>
            Kanban
        @else
            <i class="fa-solid fa-list text-indigo-500"></i>
            List
        @endif

        <i class="fa-solid fa-chevron-down text-xs text-gray-400"></i>
    </button>

    <div
        class="invisible absolute right-0 z-50 mt-1 w-40 rounded-xl border border-gray-100
            bg-white opacity-0 shadow-lg transition-all duration-200
            group-hover:visible group-hover:opacity-100"
    >
        <a
            href="{{ route('projects.show', [
                'token' => $project->token,
                'view' => 'list',
                'tab' => 'tasks',
            ]) }}"
            class="flex items-center gap-2 rounded-t-xl px-4 py-2.5 text-sm transition-colors
                {{ $currentView === 'list'
                    ? 'bg-indigo-50 font-semibold text-indigo-600'
                    : 'text-gray-600 hover:bg-gray-50' }}"
        >
            <i class="fa-solid fa-list w-4 text-center"></i>
            List
        </a>

        <a
            href="{{ route('projects.show', [
                'token' => $project->token,
                'view' => 'gantt',
                'tab' => 'tasks',
            ]) }}"
            class="flex items-center gap-2 border-y border-gray-100 px-4 py-2.5
                text-sm transition-colors
                {{ $currentView === 'gantt'
                    ? 'bg-indigo-50 font-semibold text-indigo-600'
                    : 'text-gray-600 hover:bg-gray-50' }}"
        >
            <i class="fa-solid fa-chart-gantt w-4 text-center"></i>
            Gantt
        </a>

        <a
            href="{{ route('projects.show', [
                'token' => $project->token,
                'view' => 'kanban',
                'tab' => 'tasks',
            ]) }}"
            class="flex items-center gap-2 rounded-b-xl px-4 py-2.5 text-sm transition-colors
                {{ $currentView === 'kanban'
                    ? 'bg-indigo-50 font-semibold text-indigo-600'
                    : 'text-gray-600 hover:bg-gray-50' }}"
        >
            <i class="fa-solid fa-table-columns w-4 text-center"></i>
            Kanban
        </a>
    </div>
</div>
