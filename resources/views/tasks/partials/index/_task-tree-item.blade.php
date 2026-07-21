@php
    /** @var \App\Models\Task $task */
    $task = $taskNode['task'];

    $children = $taskNode['children'] ?? collect();
    $level = $taskNode['level'] ?? 0;
    $contextOnly = $taskNode['context_only'] ?? false;
    $isAssigned = $taskNode['is_assigned'] ?? false;
    $isSummary = $task->subtasks_count > 0;

    $isOverdue =
        $task->due_date
        && $task->due_date->isPast()
        && $task->status !== 'completed';

    $isToday = $task->due_date && $task->due_date->isToday();

    $statusClasses = match ($task->status) {
        'to_do' => 'bg-amber-100 text-amber-700',
        'in_progress' => 'bg-blue-200 text-blue-800',
        'review' => 'bg-purple-200 text-purple-800',
        'completed' => 'bg-emerald-200 text-emerald-800',
        'stopped' => 'bg-red-200 text-red-800',
        'cancelled' => 'bg-zinc-300 text-zinc-800',
        default => 'bg-slate-200 text-slate-800',
    };

    $rowClasses = $isSummary
        ? 'bg-slate-50/80 hover:bg-slate-100/80'
        : 'hover:bg-gray-50';

    $leftPadding = 24 + ($level * 28);
@endphp

<tr class="{{ $rowClasses }} transition-colors">
    {{-- Task --}}
    <td class="py-4 pr-5" style="padding-left: {{ $leftPadding }}px;">
        <div class="flex min-w-[260px] items-start gap-3">
            <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center">
                @if ($level > 0)
                    <span class="text-indigo-300">
                        <i class="fa-solid fa-turn-up rotate-90 text-xs"></i>
                    </span>
                @elseif ($isSummary)
                    <span class="text-slate-500">
                        <i class="fa-solid fa-diagram-project text-sm"></i>
                    </span>
                @elseif ($task->priority === 'urgent' && $task->status !== 'completed')
                    <span
                        class="flex h-6 w-6 items-center justify-center rounded-lg
                            bg-gradient-to-br from-red-600 to-red-500
                            shadow-[0_2px_4px_rgba(220,38,38,0.3)]"
                    >
                        <i class="fa-solid fa-triangle-exclamation animate-pulse text-xs text-white"></i>
                    </span>
                @else
                    <span class="text-gray-300">
                        <i class="fa-regular fa-circle text-[9px]"></i>
                    </span>
                @endif
            </div>

            <div class="min-w-0 max-w-[280px] flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('tasks.show', $task->token) }}"
                        class="truncate text-sm font-semibold transition-colors hover:text-indigo-600
                            {{ $task->status === 'completed'
                                ? 'text-gray-400 line-through'
                                : 'text-gray-900' }}"
                        title="{{ $task->name }}"
                    >
                        {{ $task->name }}
                    </a>

                    @if ($isSummary)
                        <span
                            class="rounded-full bg-slate-200 px-2 py-0.5 text-[10px]
                                font-semibold uppercase tracking-wide text-slate-700"
                        >
                            Summary Task
                        </span>
                    @elseif ($level === 1)
                        <span
                            class="rounded-full bg-indigo-100 px-2 py-0.5 text-[10px]
                                font-semibold text-indigo-700"
                        >
                            Subtask
                        </span>
                    @elseif ($level >= 2)
                        <span
                            class="rounded-full bg-violet-100 px-2 py-0.5 text-[10px]
                                font-semibold text-violet-700"
                        >
                            Sub-subtask
                        </span>
                    @endif

                    @if ($contextOnly)
                        <span
                            class="rounded-full bg-amber-100 px-2 py-0.5 text-[10px]
                                font-semibold text-amber-700"
                            title="Ditampilkan sebagai konteks untuk task yang ditugaskan kepada Anda"
                        >
                            Context
                        </span>
                    @elseif ($isSummary && $isAssigned)
                        <span
                            class="rounded-full bg-blue-100 px-2 py-0.5 text-[10px]
                                font-semibold text-blue-700"
                        >
                            Coordinator
                        </span>
                    @endif
                </div>

                @if ($isSummary)
                    <div class="mt-1 text-xs text-gray-500">
                        Progress {{ number_format($taskNode['progress'], 1) }}%

                        <span class="mx-1 text-gray-300">·</span>

                        {{ $taskNode['completed_leaf_count'] }}
                        dari
                        {{ $taskNode['leaf_count'] }}
                        pekerjaan selesai
                    </div>
                @elseif ($task->parent)
                    <div class="mt-1 truncate text-xs text-gray-500">
                        Parent:
                        <a
                            href="{{ route('tasks.show', $task->parent->token) }}"
                            class="hover:text-indigo-700"
                        >
                            {{ $task->parent->name }}
                        </a>
                    </div>
                @endif

                @if ($task->description)
                    <p
                        class="mt-1 truncate text-xs text-gray-400"
                        title="{{ $task->description }}"
                    >
                        {{ $task->description }}
                    </p>
                @endif
            </div>
        </div>
    </td>

    {{-- Project --}}
    <td class="px-5 py-4">
        @if ($task->project)
            <span
                class="inline-flex max-w-[160px] items-center gap-2 text-sm text-gray-500"
                title="{{ $task->project->name }}"
            >
                <i class="fa-solid fa-diagram-project flex-shrink-0"></i>
                <span class="truncate">{{ $task->project->name }}</span>
            </span>
        @else
            <span class="text-sm text-gray-400">—</span>
        @endif
    </td>

    {{-- Workspace --}}
    <td class="px-5 py-4">
        @if ($task->project?->workspace)
            <span
                class="inline-flex max-w-[160px] items-center gap-2 text-sm text-gray-500"
                title="{{ $task->project->workspace->name }}"
            >
                <i class="fa-solid fa-layer-group w-5 flex-shrink-0 text-center text-sm"></i>
                <span class="truncate">{{ $task->project->workspace->name }}</span>
            </span>
        @else
            <span class="text-sm text-gray-400">—</span>
        @endif
    </td>

    {{-- Status --}}
    <td class="px-5 py-4">
        @if ($isSummary)
            @include('tasks.partials.index._summary-status', [
                'task' => $task,
            ])
        @elseif ($task->getAttribute('my_task_can_contribute'))
            <button
                id="status-btn-{{ $task->token }}"
                type="button"
                data-task-token="{{ $task->token }}"
                data-current-status="{{ $task->status }}"
                data-update-url="{{ route('tasks.updateStatus', $task->token) }}"
                data-options='@json($statusOptions)'
                onclick="openTaskStatusDropdown(this)"
                class="inline-flex w-full cursor-pointer items-center justify-between gap-1
                    rounded-md px-3 py-1 text-xs font-medium transition-opacity hover:opacity-80
                    {{ $statusClasses }}"
            >
                {{ str($task->status)->replace('_', ' ')->title() }}

                <i class="fa-solid fa-chevron-down text-[10px] opacity-60"></i>
            </button>
        @else
            <span
                class="inline-flex w-full items-center justify-center rounded-md px-3 py-1
                    text-xs font-medium {{ $statusClasses }}"
            >
                {{ str($task->status)->replace('_', ' ')->title() }}
            </span>
        @endif
    </td>

    {{-- Start Date --}}
    <td class="px-5 py-4">
        <span class="whitespace-nowrap text-sm text-gray-700">
            {{ $task->start_date?->format('d M Y') ?? '—' }}
        </span>
    </td>

    {{-- Due Date --}}
    <td class="px-5 py-4">
        @if ($task->due_date)
            <div class="flex items-center gap-2 whitespace-nowrap">
                @if ($isOverdue)
                    <span class="h-2 w-2 flex-shrink-0 animate-pulse rounded-full bg-red-500"></span>
                    <span class="text-sm font-medium text-red-600">
                        {{ $task->due_date->format('d M Y') }}
                    </span>
                @elseif ($isToday)
                    <span class="h-2 w-2 flex-shrink-0 rounded-full bg-amber-500"></span>
                    <span class="text-sm font-medium text-amber-600">
                        {{ $task->due_date->format('d M Y') }}
                    </span>
                @else
                    <span class="h-2 w-2 flex-shrink-0 rounded-full bg-emerald-500"></span>
                    <span class="text-sm text-gray-700">
                        {{ $task->due_date->format('d M Y') }}
                    </span>
                @endif
            </div>
        @else
            <span class="text-sm text-gray-400">—</span>
        @endif
    </td>

    {{-- Actions --}}
    <td class="px-6 py-4">
        <div class="flex items-center justify-center gap-1">
            <a
                href="{{ route('tasks.show', $task->token) }}"
                class="inline-flex h-8 w-8 items-center justify-center rounded-lg
                    text-blue-500 transition-colors hover:bg-blue-50"
                title="Lihat task"
            >
                <i class="fa-regular fa-eye"></i>
            </a>

            @if ($task->getAttribute('my_task_can_contribute'))
                <a
                    href="{{ route('tasks.edit', $task->token) }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg
                        text-amber-500 transition-colors hover:bg-amber-50"
                    title="Edit task"
                >
                    <i class="fa-solid fa-pen"></i>
                </a>
            @endif

            @if ($task->getAttribute('my_task_can_manage'))
                <form
                    action="{{ route('tasks.destroy', $task->token) }}"
                    method="POST"
                    class="inline"
                    onsubmit="return confirm('Delete this task?');"
                >
                    @csrf
                    @method('DELETE')

                    <input
                        type="hidden"
                        name="back_url"
                        value="{{ url()->full() }}"
                    >

                    <button
                        type="submit"
                        class="inline-flex h-8 w-8 cursor-pointer items-center justify-center
                            rounded-lg text-red-500 transition-colors hover:bg-red-50"
                        title="Hapus task"
                    >
                        <i class="fa-regular fa-trash-can"></i>
                    </button>
                </form>
            @endif
        </div>
    </td>
</tr>

@foreach ($children as $childNode)
    @include('tasks.partials.index._task-tree-item', [
        'taskNode' => $childNode,
    ])
@endforeach
