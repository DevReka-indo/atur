<div
    class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col cursor-grab active:cursor-grabbing"
    style="height: 420px;"
    data-id="widget-deadline">
    <div
        class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center text-amber-500">
                <i class="fa-solid fa-clock"></i>
            </div>

            <div>
                <h2 class="text-sm font-bold text-gray-900">
                    Deadline Approaching
                </h2>

                <p class="text-xs text-gray-500">
                    Task is due in the next 3 days
                </p>
            </div>
        </div>

        @if ($deadlineTasks->count() > 0)
            <span class="px-2.5 py-1 text-xs font-bold bg-red-100 text-red-600 rounded-full">
                {{ $deadlineTasks->count() }}
            </span>
        @endif
    </div>

    <div class="no-drag p-4 space-y-2 flex-1 overflow-y-auto">
        @forelse ($deadlineTasks as $task)
            @php
                $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($task->due_date), false);

                $urgentColor = $daysLeft < 0
                    ? 'border-red-300 bg-red-50 hover:bg-red-100'
                    : ($daysLeft <= 1
                        ? 'border-orange-300 bg-orange-50 hover:bg-orange-100'
                        : 'border-yellow-200 bg-yellow-50 hover:bg-yellow-100');

                $badgeBg = $daysLeft < 0
                    ? 'bg-red-500 text-white'
                    : 'bg-yellow-400 text-white';

                $badgeText = $daysLeft < 0
                    ? 'Late by ' . abs($daysLeft) . 'd'
                    : 'Almost Due';
            @endphp

            <div
                onclick="window.location.href='{{ route('tasks.show', $task->token) }}'"
                class="rounded-xl border {{ $urgentColor }} p-3 flex items-center justify-between gap-3 cursor-pointer transition-all select-none">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-gray-900 truncate">
                        {{ $task->name }}
                    </p>

                    @if ($task->project)
                        <p class="text-xs text-gray-500 mt-0.5 truncate">
                            <i class="fa-solid fa-folder-open mr-1"></i>
                            {{ $task->project->name }}
                        </p>
                    @endif

                    <p class="text-xs text-gray-400 mt-1">
                        <i class="fa-regular fa-calendar mr-1"></i>
                        {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                    </p>
                </div>

                <span class="flex-shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full {{ $badgeBg }}">
                    {{ $badgeText }}
                </span>
            </div>
        @empty
            <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-center">
                <p class="text-green-600 font-medium text-sm">
                    <i class="fa-solid fa-circle-check mr-1"></i>
                    There is no approaching deadline
                </p>
            </div>
        @endforelse
    </div>
</div>
