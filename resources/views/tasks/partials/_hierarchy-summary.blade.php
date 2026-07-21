@if ($task->parent_task_id !== null || $taskHasSubtasks)
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="hierarchy-summary-title">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h2 id="hierarchy-summary-title" class="text-base font-semibold text-gray-900">Task hierarchy</h2>
                    @if ($task->parent_task_id !== null)
                        <span class="rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                            {{ $taskDepth === 1 ? 'Subtask' : 'Sub-subtask' }}
                        </span>
                    @endif
                </div>
                @if ($task->parent)
                    <p class="mt-2 text-sm text-gray-600">
                        Parent:
                        <a href="{{ route('tasks.show', $task->parent->token) }}"
                            class="font-semibold text-indigo-700 hover:text-indigo-900">
                            {{ $task->parent->name }}
                        </a>
                        · kontribusi {{ number_format((float) $task->subtask_weight_percentage, 2) }}%
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-xl bg-gray-50 px-3 py-2 text-center">
                    <p class="text-xs text-gray-500">Progress</p>
                    <p class="font-bold text-gray-900">{{ number_format($hierarchyProgressPercentage, 2) }}%</p>
                </div>
                @if ($taskHasSubtasks)
                    <div class="rounded-xl bg-gray-50 px-3 py-2 text-center">
                        <p class="text-xs text-gray-500">Selesai</p>
                        <p class="font-bold text-gray-900">{{ $task->completed_subtasks_count }}/{{ $task->subtasks_count }}</p>
                    </div>
                    <div class="rounded-xl bg-gray-50 px-3 py-2 text-center">
                        <p class="text-xs text-gray-500">Bobot child</p>
                        <p class="font-bold text-gray-900">{{ number_format((float) $task->subtask_weight_total, 2) }}%</p>
                    </div>
                    <div class="rounded-xl px-3 py-2 text-center {{ (float) $task->subtask_weight_total === 100.0 ? 'bg-emerald-50 text-emerald-800' : 'bg-amber-50 text-amber-800' }}">
                        <p class="text-xs">Kesiapan</p>
                        <p class="font-bold">{{ (float) $task->subtask_weight_total === 100.0 ? 'Complete' : 'Belum lengkap' }}</p>
                    </div>
                @endif
            </div>
        </div>

        @if ($taskHasSubtasks)
            <progress value="{{ min(100, $hierarchyProgressPercentage) }}" max="100"
                class="mt-4 h-2 w-full overflow-hidden rounded-full accent-indigo-500">
                {{ number_format($hierarchyProgressPercentage, 2) }}%
            </progress>
        @endif
    </section>
@endif
