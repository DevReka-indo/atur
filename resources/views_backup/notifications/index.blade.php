@extends('layouts.app')

@section('title', 'Notifikasi')

<style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fadeIn {
            animation: fadeInUp 0.5s cubic-bezier(0.22, 1, 0.36, 1) forwards;
        }
</style>

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-[#f8fafc] via-[#f0f9ff] to-[#eef2ff] px-4 pt-4 pb-8 sm:px-6 lg:px-10">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-6
                bg-white/70 backdrop-blur-md border border-white/40
                rounded-2xl px-6 py-4 shadow-sm">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Notification</h1>
                <p class="mt-1 text-sm text-gray-500">All activities and reminders for you.</p>
            </div>
            <div class="flex items-center gap-2">
                {{-- Tombol Delete Selected (muncul kalau ada yang dicentang) --}}
                <button id="btn-delete-selected" onclick="deleteSelected()"
                    class="hidden items-center gap-2 px-4 py-2 bg-white/80 backdrop-blur text-gray-500 border border-gray-200 hover:border-gray-300"
                    <i class="fa-solid fa-trash"></i>
                    Delete Selected
                    <span id="selected-count"
                        class="bg-white text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">0</span>
                </button>

                {{-- Mark All Read --}}
                @if ($unreadCount > 0)
                    <form method="POST" action="{{ route('notifications.readAll') }}">
                        @csrf
                        <button type="submit"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-[#ADE8F4] hover:bg-[#90d8e8] text-gray-700 text-sm font-medium rounded-lg transition">
                            <i class="fa-solid fa-check-double"></i>
                            Mark All Read
                            <span class="bg-white text-gray-700 text-xs font-bold px-2 py-0.5 rounded-full">
                                {{ $unreadCount }}
                            </span>
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ===== KOLOM KIRI: Notifikasi ===== --}}
            <div class="lg:col-span-2">

                {{-- Filter Tabs + Select All --}}
                <div class="flex items-center justify-between mb-4 flex-wrap gap-2">
                    <div class="flex gap-2 flex-wrap">
                        @foreach (['all' => 'Semua', 'assignment' => 'Assignment', 'status_change' => 'Status', 'project_added' => 'Project'] as $key => $label)
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
                        <input type="checkbox" id="select-all"
                            class="rounded border-gray-300 text-indigo-600 cursor-pointer">
                        Select All
                    </label>
                </div>

                {{-- Notifikasi List --}}
                <div class="space-y-3 bg-white/50 backdrop-blur-sm p-3 rounded-2xl border border-white/40 overflow-y-auto" style="max-height: 500px;" id="notifications-list">
                    @forelse($notifications as $notif)
                        @php
                            $isRead = $notif->isRead();
                        @endphp

                        <div class="notif-item animate-fadeIn group relative overflow-hidden rounded-2xl border transition-all duration-300
                                {{ $isRead ? 'bg-white/60 border-gray-200' : 'bg-gradient-to-br from-cyan-50/70 to-indigo-50/70 border-cyan-200' }}
                                backdrop-blur-xl hover:shadow-2xl hover:-translate-y-[6px] hover:scale-[1.02]"
                                style="animation-delay: {{ $loop->index * 0.05 }}s"
                                data-type="{{ $notif->type }}">
                            <div class="flex items-start gap-3 p-4">

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
                                            <span class="w-2 h-2 rounded-full bg-indigo-500 flex-shrink-0"></span>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600">{{ $notif->message }}</p>
                                    <p class="text-xs text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-3 mt-2 flex-wrap">
                                        @if ($notif->task_id)
                                            <a href="{{ route('tasks.show', $notif->task_id) }}"
                                                class="text-xs text-indigo-600 hover:underline font-medium">
                                                See Task <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                            </a>
                                        @endif
                                        @if ($notif->project_id)
                                            <a href="{{ route('projects.show', $notif->project_id) }}"
                                                class="text-xs text-emerald-600 hover:underline font-medium">
                                                See Project <i class="fa-solid fa-arrow-right text-[10px]"></i>
                                            </a>
                                        @endif
                                        <form method="POST" action="{{ route('notifications.destroy', $notif->id) }}"
                                            onsubmit="return confirm('Hapus notifikasi ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="text-xs text-red-400 hover:text-red-600 font-medium">
                                                <i class="fa-solid fa-trash text-[10px]"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                {{-- Mark Read --}}
                                <div class="flex-shrink-0">
                                    @if (!$isRead)
                                        <form method="POST" action="{{ route('notifications.read', $notif->id) }}">
                                            @csrf
                                            <button type="submit"
                                                class="text-xs text-indigo-500 hover:text-indigo-700 font-medium whitespace-nowrap">
                                                Mark Read
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-xs text-emerald-500 whitespace-nowrap">
                                            <i class="fa-solid fa-check"></i> Read
                                        </span>
                                    @endif
                                </div>

                            </div>
                        </div>
                    @empty
                        <div class="text-center py-16 bg-white/80 backdrop-blur rounded-2xl border border-white/40 shadow-sm border-gray-200">
                            <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mx-auto mb-3">
                                <i class="fa-solid fa-bell text-2xl text-gray-300"></i>
                            </div>
                            <p class="text-gray-600 font-medium">No notification yet</p>
                            <p class="text-gray-400 text-sm mt-1">Notifications will appear here</p>
                        </div>
                    @endforelse
                </>

            </div>

            {{-- Deadline --}}
            <div class="bg-white/70 backdrop-blur-md p-4 rounded-2xl border border-white/40 shadow-sm flex flex-col" style="max-height: 500px;">
                <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2 mb-3 flex-shrink-0">
                        <i class="fa-solid fa-clock text-amber-500"></i>
                        Deadline Approaching
                    @if ($deadlineTasks->count() > 0)
                        <span class="bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">
                            {{ $deadlineTasks->count() }}
                        </span>
                    @endif
                </h2>
                <div class="overflow-y-auto flex-1">

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

                    <div class="rounded-2xl border shadow-sm hover:shadow-md transition-all {{ $cardStyle }} p-4 shadow-sm">
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

                        <a href="{{ route('tasks.show', $task->id) }}"
                            class="inline-flex items-center gap-1 mt-2 text-xs font-medium text-indigo-600 hover:underline">
                            Open Task <i class="fa-solid fa-arrow-right text-[10px]"></i>
                        </a>
                    </div>
                @empty
                    <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-center">
                        <p class="text-emerald-600 font-medium text-sm">
                            <i class="fa-solid fa-circle-check mr-1"></i> No deadline approaching
                        </p>
                    </div>
                @endforelse
            </div>

        </div>
    </div>

    <script>
        const selectAll = document.getElementById('select-all');
        const btnDelete = document.getElementById('btn-delete-selected');
        const countBadge = document.getElementById('selected-count');

        function updateDeleteBtn() {
            const checked = document.querySelectorAll('.notif-checkbox:checked');
            const count = checked.length;
            countBadge.textContent = count;
            if (count > 0) {
                btnDelete.classList.remove('hidden');
                btnDelete.classList.add('inline-flex');
            } else {
                btnDelete.classList.add('hidden');
                btnDelete.classList.remove('inline-flex');
            }
        }

        selectAll.addEventListener('change', function() {
            const visible = document.querySelectorAll('.notif-item:not([style*="display: none"]) .notif-checkbox');
            visible.forEach(cb => cb.checked = this.checked);
            updateDeleteBtn();
        });

        document.addEventListener('change', function(e) {
            if (e.target.classList.contains('notif-checkbox')) {
                updateDeleteBtn();
                const allVisible = document.querySelectorAll(
                    '.notif-item:not([style*="display: none"]) .notif-checkbox');
                const allChecked = [...allVisible].every(cb => cb.checked);
                selectAll.checked = allChecked;
            }
        });

        function deleteSelected() {
            const checked = document.querySelectorAll('.notif-checkbox:checked');
            if (checked.length === 0) return;
            if (!confirm(`Hapus ${checked.length} notifikasi yang dipilih?`)) return;

            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
            const ids = [...checked].map(cb => cb.value);
            const promises = ids.map(id =>
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
                })
            );

            Promise.all(promises).then(() => {
                window.location.reload();
            });
        }

        // ===== FILTER TABS =====
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
                    item.style.display = (tab === 'all' || item.dataset.type === tab) ? '' : 'none';
                });

                selectAll.checked = false;
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
        setInterval(pollNotifications, 30000);
        pollNotifications();
    </script>
@endsection
