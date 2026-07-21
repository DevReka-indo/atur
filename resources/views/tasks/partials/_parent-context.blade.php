@if ($parentTask)
    <section class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-5" aria-labelledby="parent-context-title">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p id="parent-context-title" class="text-xs font-semibold uppercase tracking-wide text-indigo-600">
                    Parent task
                </p>
                <a href="{{ route('tasks.show', $parentTask->token) }}"
                    class="mt-1 block truncate text-lg font-semibold text-gray-900 hover:text-indigo-700">
                    {{ $parentTask->name }}
                </a>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $parentTask->project->name }} · Level {{ $parentDepth + 1 }}
                </p>
            </div>

            <div class="grid grid-cols-2 gap-3 text-center sm:min-w-64">
                <div class="rounded-xl border border-white bg-white/80 px-3 py-2">
                    <p class="text-xs text-gray-500">Bobot terpakai</p>
                    <p class="text-base font-bold text-gray-900">{{ number_format($usedSubtaskWeight, 2) }}%</p>
                </div>
                <div class="rounded-xl border border-white bg-white/80 px-3 py-2">
                    <p class="text-xs text-gray-500">Sisa tersedia</p>
                    <p class="text-base font-bold text-indigo-700">{{ number_format($remainingSubtaskWeight, 2) }}%</p>
                </div>
            </div>
        </div>
    </section>
@endif
