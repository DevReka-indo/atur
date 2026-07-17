@php
    $statusConfig = [
        'to_do' => [
            'class' => 'bg-gray-100 text-gray-700 border-gray-200',
            'icon' => 'fa-circle-dot',
            'label' => 'To Do',
        ],
        'in_progress' => [
            'class' => 'bg-blue-50 text-blue-700 border-blue-200',
            'icon' => 'fa-spinner',
            'label' => 'In Progress',
        ],
        'review' => [
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
            'icon' => 'fa-eye',
            'label' => 'Review',
        ],
        'completed' => [
            'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'icon' => 'fa-circle-check',
            'label' => 'Completed',
        ],
        'blocked' => [
            'class' => 'bg-red-50 text-red-700 border-red-200',
            'icon' => 'fa-circle-exclamation',
            'label' => 'Blocked',
        ],
        'cancelled' => [
            'class' => 'bg-gray-50 text-gray-400 border-gray-200 line-through',
            'icon' => 'fa-circle-xmark',
            'label' => 'Cancelled',
        ],
        'urgent' => [
            'class' => 'bg-red-100 text-red-400 border-red-200 line-through',
            'icon' => 'fa-circle-xmark',
            'label' => 'Urgent',
        ],
    ];

    $recentActivities = $recentTasks->sortByDesc('updated_at')->take(10);
@endphp

<div
    class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col cursor-grab active:cursor-grabbing"
    style="height: 420px;"
    data-id="widget-recent">
    <div
        class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7]">
                <i class="fa-regular fa-clock"></i>
            </div>

            <div>
                <h2 class="text-sm font-bold text-gray-900">
                    Recent Tasks
                </h2>

                <p class="text-xs text-gray-500">
                    Team latest activity
                </p>
            </div>
        </div>

        <a href="{{ route('tasks.index') }}"
            class="no-drag text-xs font-medium text-indigo-600 hover:text-indigo-700">
            See all
            <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
        </a>
    </div>

    @if ($recentActivities->isEmpty())
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div
                    class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-regular fa-clipboard text-2xl text-indigo-600"></i>
                </div>

                <p class="text-gray-700 font-semibold text-sm">
                    No recent activity
                </p>

                <p class="text-xs text-gray-400 mt-1">
                    Your recent tasks will appear here.
                </p>

                <a href="{{ route('tasks.create') }}"
                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                    <i class="fa-solid fa-plus"></i>
                    Create Task
                </a>
            </div>
        </div>
    @else
        <div class="no-drag divide-y divide-gray-100 flex-1 overflow-y-auto">
            @foreach ($recentActivities as $task)
                @php
                    $config = $statusConfig[$task->status] ?? $statusConfig['to_do'];
                    $isOverdue = $task->due_date?->isPast() && $task->status !== 'completed';
                    $isUrgent = $task->priority === 'urgent' && $task->status !== 'completed';
                    $isCompleted = $task->status === 'completed';
                @endphp

                <div
                    class="px-5 py-3 flex items-center justify-between gap-3 {{ $isUrgent ? 'bg-red-50/60 border-l-2 border-red-400' : 'hover:bg-gray-50' }} {{ $isCompleted ? 'opacity-75' : '' }} transition-colors cursor-pointer group"
                    onclick="window.location.href='{{ route('tasks.show', $task->token) }}'">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            class="w-8 h-8 rounded-lg {{ $isCompleted ? 'bg-gradient-to-br from-emerald-100 to-green-100 text-emerald-600' : ($isUrgent ? 'bg-gradient-to-br from-red-100 to-orange-100 text-red-600' : 'bg-gradient-to-br from-indigo-100 to-[#A3E1EE] text-[#0096c7]') }} font-semibold text-xs flex-shrink-0 flex items-center justify-center">
                            @if ($isCompleted)
                                <i class="fa-solid fa-check text-xs"></i>
                            @else
                                {{ strtoupper(substr($task->name, 0, 1)) }}
                            @endif
                        </div>

                        <div class="min-w-0">
                            <p
                                class="text-sm font-medium {{ $isCompleted ? 'line-through text-gray-400' : ($isUrgent ? 'text-red-700' : 'text-gray-900 group-hover:text-indigo-600') }} transition-colors truncate">
                                {{ $task->name }}

                                @if ($isUrgent)
                                    <span
                                        class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-200 text-red-700">
                                        URGENT
                                    </span>
                                @endif
                            </p>

                            <p class="text-xs text-gray-400 truncate">
                                {{ $task->project?->name ?? '—' }}
                            </p>

                            <p class="text-[10px] text-gray-400 mt-0.5">
                                Updated {{ $task->updated_at->diffForHumans() }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2 flex-shrink-0">
                        <span
                            class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border {{ $config['class'] }}">
                            <i class="fa-solid {{ $config['icon'] }} text-[9px]"></i>
                            {{ $config['label'] }}
                        </span>

                        @if ($task->due_date)
                            <span class="text-xs {{ $isOverdue ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                {{ $task->due_date->format('d M') }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
