@php
    $completionRate = $stats['assigned_tasks'] > 0
        ? round(($stats['completed_tasks'] / $stats['assigned_tasks']) * 100)
        : 0;
@endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 px-4 sm:px-6">
    <a href="{{ route('projects.index') }}"
        class="block bg-white rounded-xl border-t-4 border-blue-500 shadow-sm px-5 py-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                Overall Projects
            </p>

            <div class="p-2 bg-blue-50 rounded-lg">
                <i class="fa-solid fa-folder-tree text-blue-600 text-lg"></i>
            </div>
        </div>

        <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">
            {{ $stats['total_projects'] }}
        </p>

        <p class="text-xs text-blue-600 font-medium flex items-center gap-1">
            <i class="fa-solid fa-arrow-trend-up text-[10px]"></i>
            Across all workspaces
        </p>
    </a>

    <a href="{{ route('workspaces.index') }}"
        class="block bg-white rounded-xl border-t-4 border-emerald-500 shadow-sm px-5 py-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                Active Workspaces
            </p>

            <div class="p-2 bg-emerald-50 rounded-lg">
                <i class="fa-solid fa-layer-group text-emerald-600 text-lg"></i>
            </div>
        </div>

        <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">
            {{ $stats['total_workspaces'] }}
        </p>

        <p class="text-xs text-emerald-600 font-medium">
            On going
        </p>
    </a>

    <a href="{{ route('projects.index', ['status' => 'completed']) }}"
        class="block bg-white rounded-xl border-t-4 border-violet-500 shadow-sm px-5 py-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                Completed
            </p>

            <div class="p-2 bg-violet-50 rounded-lg">
                <i class="fa-solid fa-clipboard-check text-violet-600 text-lg"></i>
            </div>
        </div>

        <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">
            {{ $stats['completed_tasks'] }}
        </p>

        <p class="text-xs text-violet-600 font-medium flex items-center gap-1">
            {{ $completionRate }}% completion rate
        </p>
    </a>

    <a href="{{ route('tasks.index') }}"
        class="block bg-white rounded-xl border-t-4 border-amber-500 shadow-sm px-5 py-5 hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
        <div class="flex items-center justify-between mb-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">
                Assigned Tasks
            </p>

            <div class="p-2 bg-amber-50 rounded-lg">
                <i class="fa-solid fa-clock text-amber-600 text-lg"></i>
            </div>
        </div>

        <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">
            {{ $stats['assigned_tasks'] }}
        </p>

        <p class="text-xs text-amber-600 font-medium">
            Attention Required
        </p>
    </a>
</div>
