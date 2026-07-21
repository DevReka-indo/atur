<div class="p-6">
    <div class="rounded-2xl border border-gray-200 bg-white p-4 shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 pb-4">
            <div>
                <h3 class="font-semibold text-gray-900">Task hierarchy</h3>
                <p class="mt-1 text-sm text-gray-500">Task utama dan turunannya ditampilkan sampai tiga level.</p>
            </div>
            <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600">
                {{ $project->tasks_count }} task
            </span>
        </div>

        <div class="mt-4 grid gap-3">
            @forelse ($taskHierarchyRoots as $hierarchyTask)
                @include('projects.partials._task-hierarchy-item', [
                    'hierarchyTask' => $hierarchyTask,
                    'canContribute' => $canContribute,
                ])
            @empty
                <div class="py-12 text-center">
                    <p class="font-semibold text-gray-700">No tasks found</p>
                    <p class="mt-1 text-sm text-gray-400">Create your first task to get started.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
