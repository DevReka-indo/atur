@extends('layouts.app')

@section('title', 'Notifikasi')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    {{-- Header --}}
    <div
        class="flex items-center justify-between mb-6 bg-white/70 backdrop-blur-md border border-white/40 rounded-2xl px-6 py-4 shadow-sm">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Notification</h1>
            <p class="mt-1 text-sm text-gray-500">All activities and reminders for you.</p>
        </div>
        <div class="flex items-stretch gap-2">

            {{-- Delete Selected --}}
            <button id="btn-delete-selected" onclick="deleteSelected()"
                class="inline-flex items-center gap-2 px-4 py-2 h-10 bg-white/80 backdrop-blur text-gray-500 border border-gray-200 hover:border-gray-300 rounded-lg text-sm font-medium transition">
                <i class="fa-solid fa-trash"></i>
                Delete Selected
                <span id="selected-count" class="bg-white text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">0</span>
            </button>

            {{-- Mark All Read --}}
            <form method="POST" action="{{ route('notifications.readAll') }}" class="flex">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 h-10 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition">
                    <i class="fa-solid fa-check-double"></i>
                    Mark All Read
                    @if ($unreadCount > 0)
                        <span class="bg-white text-indigo-600 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ $unreadCount }}
                        </span>
                    @endif
                </button>
            </form>

        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <div class="lg:col-span-2">
            {{-- Filter Tabs + Select All --}}
            <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                <div class="flex gap-2 flex-wrap">
                    @foreach (['all' => 'All', 'assignment' => 'Assignment', 'status_change' => 'Status', 'project_added' => 'Project'] as $key => $label)
                        <button
                            class="notif-tab px-4 py-2 rounded-lg text-sm font-medium transition-all duration-200
                            {{ $key === 'all' ? 'bg-[#ADE8F4] text-gray-700' : 'bg-white text-gray-500 border border-gray-200 hover:border-gray-300 hover:text-gray-700' }}"
                            data-tab="{{ $key }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>

                {{-- Select All --}}
                <label class="flex items-center gap-2 text-sm text-gray-500 cursor-pointer select-none">
                    <input type="checkbox" id="select-all" class="rounded border-gray-300 text-indigo-600 cursor-pointer">
                    Select All
                </label>
            </div>

            {{-- Notifikasi List --}}
            <div class="space-y-3 bg-white backdrop-blur-sm p-3 rounded-2xl border border-white/40 overflow-y-auto max-h-[500px]"
                id="notifications-list">

                @forelse($notifications as $notif)
                    @php $isRead = $notif->isRead(); @endphp

                    @php
                        $url = null;
                        if ($notif->task_id && $notif->task) {
                            $url = route('tasks.show', $notif->task->token);
                        } elseif ($notif->project_id && $notif->project) {
                            $url = route('projects.show', $notif->project->token);
                        }
                        $delayClass = match (true) {
                            $loop->index < 1 => 'delay-0',
                            $loop->index < 2 => 'delay-75',
                            $loop->index < 3 => 'delay-100',
                            $loop->index < 4 => 'delay-150',
                            $loop->index < 5 => 'delay-200',
                            $loop->index < 6 => 'delay-300',
                            default => 'delay-500',
                        };
                    @endphp

                    <div class="notif-item opacity-0 translate-y-2 transition-all duration-500 ease-out {{ $delayClass }} group relative overflow-hidden rounded-2xl border
                        {{ $isRead ? 'bg-white/60 border-gray-200' : 'bg-pink-100 border-pink-400' }}
                        backdrop-blur-xl hover:shadow-2xl hover:-translate-y-1 hover:scale-[1.02]
                        {{ $url ? 'cursor-pointer' : '' }}"
                        data-type="{{ $notif->type }}" data-url="{{ $url ?? '' }}" data-id="{{ $notif->id }}">

                        <div class="flex items-center justify-between gap-3 p-4">

                            {{-- Checkbox --}}
                            <div class="pt-0.5 flex-shrink-0">
                                <input type="checkbox"
                                    class="notif-checkbox rounded border-gray-300 text-indigo-600 cursor-pointer"
                                    value="{{ $notif->id }}">
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <p class="text-sm font-semibold text-gray-900">{{ $notif->title }}</p>
                                    @if (!$isRead)
                                        <span class="w-2 h-2 rounded-full bg-pink-500 flex-shrink-0"></span>
                                    @endif
                                </div>
                                <p class="text-sm text-gray-600">{{ $notif->message }}</p>
                                <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>

                                {{-- Actions --}}
                                <div class="flex items-center gap-4 mt-2">
                                    <button onclick="deleteNotif({{ $notif->id }}, this)"
                                        class="inline-flex items-center gap-1 text-xs text-red-400 hover:text-red-600 font-medium">
                                        <i class="fa-solid fa-trash text-[10px]"></i> Delete
                                    </button>
                                </div>
                            </div>

                            {{-- Mark Read --}}
                            <div class="flex-shrink-0" id="mark-read-{{ $notif->id }}">
                                @if (!$isRead)
                                    <button onclick="markRead({{ $notif->id }}, this)"
                                        class="text-xs text-indigo-500 hover:text-indigo-700 font-medium whitespace-nowrap">
                                        Mark Read
                                    </button>
                                @else
                                    <span class="text-xs text-emerald-500 whitespace-nowrap">
                                        <i class="fa-solid fa-check"></i> Read
                                    </span>
                                @endif
                            </div>

                        </div>
                    </div>
                @empty
                    <div
                        class="text-center py-16 bg-white/80 backdrop-blur rounded-2xl border border-white/40 shadow-sm border-gray-200">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-solid fa-bell text-2xl text-gray-300"></i>
                        </div>
                        <p class="text-gray-600 font-medium">No notification yet</p>
                        <p class="text-gray-400 text-sm mt-1">Notifications will appear here</p>
                    </div>
                @endforelse

            </div>
        </div>

        {{-- Urgent & Deadline --}}
        <div
            class="bg-white/70 backdrop-blur-md p-4 rounded-2xl border border-white/40 shadow-sm flex flex-col max-h-[500px]">

            {{-- URGENT TASKS WARNING --}}
            @if ($urgentTasks->count() > 0)
                <div class="mb-4 pb-4 border-b border-red-200">
                    <h2 class="text-base font-semibold text-red-700 flex items-center gap-2 mb-3">
                        <i class="fa-solid fa-triangle-exclamation animate-pulse text-red-500"></i>
                        Urgent
                        <span class="bg-amber-100 text-amber-600 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ $urgentTasks->count() }}
                        </span>
                    </h2>

                    <div class="space-y-2 max-h-[200px] overflow-y-auto pr-1">
                        @foreach ($urgentTasks as $task)
                            @php
                                $daysLeft = $task->due_date
                                    ? (int) now()->diffInDays(\Carbon\Carbon::parse($task->due_date), false)
                                    : null;
                                $isOverdue = $daysLeft !== null && $daysLeft <= 0;

                                $cardStyle = $isOverdue
                                    ? 'border-red-200 bg-red-50'
                                    : ($daysLeft <= 1
                                        ? 'border-orange-200 bg-orange-50'
                                        : 'border-yellow-200 bg-yellow-50');
                                $textColor = $isOverdue
                                    ? 'text-red-600'
                                    : ($daysLeft <= 1
                                        ? 'text-orange-600'
                                        : 'text-yellow-600');
                            @endphp
                            <div class="rounded-2xl border shadow-sm hover:shadow-md transition-all cursor-pointer p-4 {{ $cardStyle }}"
                                onclick="window.location.href='{{ route('tasks.show', $task->token) }}'">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="text-sm font-semibold text-gray-900">{{ $task->name }}</p>
                                    <i
                                        class="fa-solid fa-circle-exclamation {{ $isOverdue ? 'text-red-500' : 'text-amber-500' }} text-xs flex-shrink-0 mt-0.5"></i>
                                </div>

                                @if ($task->project)
                                    <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                        <i class="fa-solid fa-folder-open"></i>
                                        {{ $task->project->name }}
                                    </p>
                                @endif

                                @if ($task->due_date)
                                    <p class="text-xs {{ $textColor }} font-semibold mt-2 flex items-center gap-1">
                                        @if ($isOverdue)
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            Overdue: {{ \Carbon\Carbon::parse($task->due_date)->format('d M') }}
                                        @elseif($daysLeft === 1)
                                            <i class="fa-solid fa-fire"></i>
                                            Tomorrow: {{ \Carbon\Carbon::parse($task->due_date)->format('d M') }}
                                        @else
                                            <i class="fa-solid fa-calendar"></i>
                                            Due: {{ \Carbon\Carbon::parse($task->due_date)->format('d M') }}
                                            @if ($daysLeft <= 2)
                                                <span
                                                    class="ml-1 px-1.5 py-0.5 bg-red-200 text-red-800 rounded text-[10px] font-bold">
                                                    {{ $daysLeft }}d
                                                </span>
                                            @endif
                                        @endif
                                    </p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- DEADLINE APPROACHING --}}
            <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-3 flex-shrink-0">
                <i class="fa-solid fa-clock text-amber-500"></i>
                Deadline Approaching
                @if ($deadlineTasks->count() > 0)
                    <span class="bg-amber-100 text-amber-600 text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ $deadlineTasks->count() }}
                    </span>
                @endif
            </h2>

            <div class="overflow-y-auto flex-1 space-y-3">
                @forelse($deadlineTasks as $task)
                    @php
                        $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($task->due_date), false);
                        $cardStyle =
                            $daysLeft <= 0
                                ? 'border-red-200 bg-red-50'
                                : ($daysLeft <= 1
                                    ? 'border-orange-200 bg-orange-50'
                                    : 'border-yellow-200 bg-yellow-50');
                        $textColor =
                            $daysLeft <= 0 ? 'text-red-600' : ($daysLeft <= 1 ? 'text-orange-600' : 'text-yellow-600');
                    @endphp

                    <div class="rounded-2xl border shadow-sm hover:shadow-md transition-all cursor-pointer {{ $cardStyle }} p-4"
                        onclick="window.location.href='{{ route('tasks.show', $task->token) }}'">
                        <p class="text-sm font-semibold text-gray-900">{{ $task->name }}</p>

                        @if ($task->project)
                            <p class="text-xs text-gray-500 mt-0.5 flex items-center gap-1">
                                <i class="fa-solid fa-folder-open"></i>
                                {{ $task->project->name }}
                            </p>
                        @endif

                        <p class="text-xs {{ $textColor }} font-semibold mt-2 flex items-center gap-1">
                            @if ($daysLeft <= 0)
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                Overdue: {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                            @elseif($daysLeft === 1)
                                <i class="fa-solid fa-fire"></i>
                                Tomorrow: {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                            @else
                                <i class="fa-solid fa-calendar-days"></i>
                                {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }} ({{ $daysLeft }} days
                                left)
                            @endif
                        </p>
                    </div>
                @empty
                    @if ($urgentTasks->count() === 0)
                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                            <p class="text-emerald-600 font-medium text-sm">
                                <i class="fa-solid fa-circle-check mr-1"></i> No urgent tasks or deadlines
                            </p>
                        </div>
                    @endif
                @endforelse
            </div>

        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.querySelectorAll('.notif-item').forEach((item, index) => {
                    setTimeout(() => {
                        item.classList.remove('opacity-0', 'translate-y-2');
                        item.classList.add('opacity-100', 'translate-y-0');
                    }, index * 50);
                });
            });

            const selectAll = document.getElementById('select-all');
            const btnDelete = document.getElementById('btn-delete-selected');
            const countBadge = document.getElementById('selected-count');

            function updateDeleteBtn() {
                const checked = document.querySelectorAll('.notif-checkbox:checked');
                countBadge.textContent = checked.length;
            }

            selectAll?.addEventListener('change', function() {
                const visible = document.querySelectorAll('.notif-item:not([style*="display: none"]) .notif-checkbox');
                visible.forEach(cb => cb.checked = this.checked);
                updateDeleteBtn();
            });

            document.addEventListener('change', function(e) {
                if (!e.target?.classList?.contains('notif-checkbox')) return;
                updateDeleteBtn();
                const allVisible = document.querySelectorAll(
                    '.notif-item:not([style*="display: none"]) .notif-checkbox');
                if (selectAll && allVisible.length > 0) {
                    selectAll.checked = [...allVisible].every(cb => cb.checked);
                }
            });

            function deleteSelected() {
                const checked = document.querySelectorAll('.notif-checkbox:checked');
                if (checked.length === 0) return;
                if (!confirm(`Hapus ${checked.length} notifikasi yang dipilih?`)) return;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const promises = [...checked].map(cb =>
                    fetch(`/notifications/${cb.value}`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': csrfToken,
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: new URLSearchParams({
                            _method: 'DELETE',
                            _token: csrfToken
                        })
                    })
                );

                Promise.all(promises).then(() => window.location.reload());
            }

            document.querySelectorAll('.notif-tab').forEach(btn => {
                btn.addEventListener('click', () => {
                    const tab = btn.dataset.tab;

                    document.querySelectorAll('.notif-tab').forEach(b => {
                        b.classList.remove('bg-[#ADE8F4]', 'text-gray-700');
                        b.classList.add('bg-white', 'text-gray-500', 'border', 'border-gray-200');
                    });
                    btn.classList.add('bg-[#ADE8F4]', 'text-gray-700');
                    btn.classList.remove('bg-white', 'text-gray-500', 'border', 'border-gray-200');

                    document.querySelectorAll('.notif-item').forEach(item => {
                        if (tab === 'all' || item.dataset.type === tab) {
                            item.style.display = '';
                        } else {
                            item.style.display = 'none';
                        }
                    });

                    if (selectAll) selectAll.checked = false;
                    updateDeleteBtn();
                });
            });

            function pollNotifications() {
                fetch('{{ route('notifications.poll') }}', {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        const badge = document.getElementById('notif-badge');
                        if (badge) {
                            badge.textContent = data.unread_count;
                            badge.style.display = data.unread_count > 0 ? '' : 'none';
                        }
                    })
                    .catch(() => {});
            }

            document.querySelectorAll('.notif-item').forEach(card => {
                card.addEventListener('click', function(e) {
                    if (e.target.closest('a, button, input, form, label')) return;

                    const url = this.dataset.url;
                    const notifId = this.dataset.id;
                    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                    if (notifId) {
                        fetch(`/notifications/${notifId}/read`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new URLSearchParams({
                                _token: csrfToken
                            })
                        });
                    }

                    if (url) window.location.href = url;
                });
            });

            function deleteNotif(id, btn) {
                if (!confirm('Hapus notifikasi ini?')) return;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                fetch(`/notifications/${id}`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        _method: 'DELETE',
                        _token: csrfToken
                    })
                }).then(res => {
                    if (res.ok) {
                        // Animasi hapus dengan Tailwind classes
                        const card = btn.closest('.notif-item');
                        card.classList.add('opacity-0', 'translate-x-4', 'pointer-events-none');
                        setTimeout(() => card?.remove(), 300);
                        updateDeleteBtn();
                    }
                });
            }

            function markRead(id, btn) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

                fetch(`/notifications/${id}/read`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: new URLSearchParams({
                        _token: csrfToken
                    })
                }).then(res => {
                    if (res.ok) {
                        const wrapper = document.getElementById(`mark-read-${id}`);
                        if (wrapper) {
                            wrapper.innerHTML =
                                `<span class="text-xs text-emerald-500 whitespace-nowrap"><i class="fa-solid fa-check"></i> Read</span>`;
                        }
                        const card = btn.closest('.notif-item');
                        if (card) {
                            card.classList.remove('bg-amber-50', 'border-amber-300');
                            card.classList.add('bg-white/60', 'border-gray-200');
                            const dot = card.querySelector('.bg-pink-500');
                            if (dot) dot.remove();
                        }
                    }
                });
            }

            setInterval(pollNotifications, 30000);
            pollNotifications();
        </script>

    @endsection
