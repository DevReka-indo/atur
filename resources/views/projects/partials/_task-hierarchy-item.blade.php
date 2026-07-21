@php
    $statusClasses = match ($hierarchyTask->status) {
        'to_do' => 'bg-amber-100 text-amber-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'review' => 'bg-purple-100 text-purple-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'stopped' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-zinc-200 text-zinc-700',
        default => 'bg-slate-100 text-slate-700',
    };

    $statusOptions = [
        ['value' => 'to_do', 'label' => 'To Do'],
        ['value' => 'in_progress', 'label' => 'In Progress'],
        ['value' => 'review', 'label' => 'Review'],
        ['value' => 'completed', 'label' => 'Completed'],
        ['value' => 'stopped', 'label' => 'Stopped'],
        ['value' => 'cancelled', 'label' => 'Cancelled'],
    ];

    $hierarchyDepth = (int) $hierarchyTask->hierarchy_depth;
    $isSummaryTask = $hierarchyTask->subtasks->isNotEmpty();
@endphp

<article
    class="rounded-xl border border-gray-200 bg-gray-50/60 p-4"
    data-hierarchy-depth="{{ $hierarchyDepth }}"
>
    <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('tasks.show', $hierarchyTask->token) }}"
                    class="truncate font-semibold text-gray-900 transition-colors hover:text-indigo-700"
                >
                    {{ $hierarchyTask->name }}
                </a>

                @if ($hierarchyDepth > 0)
                    <span
                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700"
                    >
                        {{ $hierarchyDepth === 1 ? 'Subtask' : 'Sub-subtask' }}
                    </span>
                @endif

                @if ($isSummaryTask)
                    <span
                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1
                            text-xs font-medium {{ $statusClasses }}"
                        title="Status mengikuti status subtask"
                    >
                        {{ str($hierarchyTask->status)->replace('_', ' ')->title() }}

                        <i class="fa-solid fa-lock text-[9px] opacity-60"></i>
                    </span>
                @elseif ($canContribute)
                    <button
                        id="status-btn-{{ $hierarchyTask->token }}"
                        type="button"
                        data-current-status="{{ $hierarchyTask->status }}"
                        data-update-url="{{ route('tasks.updateStatus', $hierarchyTask->token) }}"
                        data-options='@json($statusOptions)'
                        onclick="openTaskStatusDropdown(this)"
                        class="inline-flex cursor-pointer items-center gap-1.5 rounded-full
                            px-2.5 py-1 text-xs font-medium transition hover:opacity-80
                            {{ $statusClasses }}"
                        title="Ubah status task"
                    >
                        {{ str($hierarchyTask->status)->replace('_', ' ')->title() }}

                        <i class="fa-solid fa-chevron-down text-[9px] opacity-60"></i>
                    </button>
                @else
                    <span
                        class="inline-flex items-center rounded-full px-2.5 py-1
                            text-xs font-medium {{ $statusClasses }}"
                    >
                        {{ str($hierarchyTask->status)->replace('_', ' ')->title() }}
                    </span>
                @endif
            </div>

            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500">
                <span>
                    Progress
                    {{ number_format((float) $hierarchyTask->hierarchy_progress_percentage, 2) }}%
                </span>

                @if ($hierarchyDepth > 0)
                    <span>
                        Bobot parent
                        {{ number_format((float) $hierarchyTask->subtask_weight_percentage, 2) }}%
                    </span>
                @elseif ($hierarchyTask->subtasks_count > 0)
                    <span>
                        Bobot child
                        {{ number_format((float) $hierarchyTask->subtask_weight_total, 2) }}%
                    </span>
                @else
                    <span>
                        Bobot project
                        {{ number_format((float) $hierarchyTask->weight, 2) }}
                    </span>
                @endif

                <span>
                    {{ $hierarchyTask->start_date?->format('d M Y') ?? '—' }}
                    →
                    {{ $hierarchyTask->due_date?->format('d M Y') ?? '—' }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('tasks.show', $hierarchyTask->token) }}"
                class="rounded-lg border border-gray-200 bg-white px-3 py-1.5
                    text-xs font-semibold text-gray-700 transition-colors hover:bg-gray-100"
            >
                Lihat
            </a>

            @if ($canContribute)
                <a
                    href="{{ route('tasks.edit', $hierarchyTask->token) }}"
                    class="rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5
                        text-xs font-semibold text-indigo-700 transition-colors hover:bg-indigo-100"
                >
                    Edit
                </a>
            @endif
        </div>
    </div>

    @if ($isSummaryTask && $hierarchyDepth < 2)
        <div class="mt-3 grid gap-3 border-l-2 border-indigo-200 pl-4">
            @foreach ($hierarchyTask->subtasks as $childTask)
                @include('projects.partials._task-hierarchy-item', [
                    'hierarchyTask' => $childTask,
                    'canContribute' => $canContribute,
                ])
            @endforeach
        </div>
    @endif
</article>
