<div class="absolute inset-0 overflow-auto">
    <table class="min-w-full border-separate border-spacing-0">
        <thead class="sticky top-0 z-20">
            <tr class="bg-[#ADE8F4]">
                <th
                    class="border-b border-[#ADE8F4] px-6 py-4 text-left text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Task
                </th>

                <th
                    class="border-b border-[#ADE8F4] px-5 py-4 text-left text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Project
                </th>

                <th
                    class="border-b border-[#ADE8F4] px-5 py-4 text-left text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Workspace
                </th>

                <th
                    class="border-b border-[#ADE8F4] px-5 py-4 text-left text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Status
                </th>

                <th
                    class="border-b border-[#ADE8F4] px-5 py-4 text-left text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Start Date
                </th>

                <th
                    class="border-b border-[#ADE8F4] px-5 py-4 text-left text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Due Date
                </th>

                <th
                    class="border-b border-[#ADE8F4] px-6 py-4 text-center text-xs font-semibold
                        uppercase tracking-wider text-gray-600 whitespace-nowrap"
                >
                    Actions
                </th>
            </tr>
        </thead>

        <tbody class="divide-y divide-gray-100">
            @forelse ($taskTree as $taskNode)
                @include('tasks.partials.index._task-tree-item', [
                    'taskNode' => $taskNode,
                ])
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-16 text-center">
                        <div class="flex flex-col items-center gap-3">
                            <div class="flex h-16 w-16 items-center justify-center rounded-full bg-amber-50">
                                <i class="fa-solid fa-list-check text-xl text-amber-500"></i>
                            </div>

                            <div>
                                <p class="text-sm font-semibold text-gray-700">
                                    No tasks found
                                </p>

                                <p class="mt-1 text-xs text-gray-400">
                                    {{ $currentStatus !== 'all'
                                        ? 'No tasks match the selected status.'
                                        : 'Create your first task to get started.' }}
                                </p>
                            </div>
                        </div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
