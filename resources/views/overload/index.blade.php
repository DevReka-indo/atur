@extends('layouts.app')

@section('title', 'Member Overload')

@section('content')
    <div style="height: calc(100vh - 121px); display: flex; flex-direction: column; overflow: hidden;">
        <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

        {{-- Header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-4xl font-semibold text-slate-900">
                    Member Overload
                </h1>
                <button onclick="openOverloadInfoModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                @if ($isSuperAdmin)
                    Menampilkan semua member overload di seluruh project
                @else
                    Menampilkan member overload di project yang kamu ikuti
                @endif
            </p>
        </div>

        {{-- Main Card --}}
        <div class="border border-gray-200 shadow-sm rounded-xl flex-1 overflow-hidden bg-white"
            style="min-height:0; position:relative;">

            <div style="position:absolute; inset:0; overflow-x:auto; overflow-y:auto;">

                {{-- Card Header (sticky) --}}
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-3"
                    style="background: linear-gradient(to right, #eff6ff, #dbeafe);">
                    <span class="text-xs font-semibold uppercase tracking-wider text-blue-700">
                        <i class="fa-solid fa-user-clock mr-1"></i>
                        {{ $overloadedMembers->count() }} member overload ditemukan
                    </span>
                    <span class="text-xs text-blue-600 font-medium">
                        Batas overload: <strong>≥ 5 task aktif</strong>
                    </span>
                </div>

                {{-- Rows --}}
                @forelse ($overloadedMembers as $om)
                    @php
                        $count = $om['task_count'];
                        if ($count >= 8) {
                            $iconBg = 'bg-red-100';
                            $iconColor = 'text-red-500';
                            $numColor = 'text-red-600';
                            $tagBg = 'bg-red-100 text-red-700';
                            $rowHover = 'hover:bg-red-50/40';
                            $textBold = 'text-red-600';
                            $taskDot = 'bg-red-400';
                            $badge =
                                '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-red-500 text-white ml-2">KRITIS</span>';
                        } elseif ($count >= 6) {
                            $iconBg = 'bg-orange-100';
                            $iconColor = 'text-orange-500';
                            $numColor = 'text-orange-600';
                            $tagBg = 'bg-orange-100 text-orange-700';
                            $rowHover = 'hover:bg-orange-50/40';
                            $textBold = 'text-orange-600';
                            $taskDot = 'bg-orange-400';
                            $badge =
                                '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-orange-400 text-white ml-2">TINGGI</span>';
                        } else {
                            $iconBg = 'bg-amber-100';
                            $iconColor = 'text-amber-500';
                            $numColor = 'text-amber-600';
                            $tagBg = 'bg-amber-100 text-amber-700';
                            $rowHover = 'hover:bg-amber-50/40';
                            $textBold = 'text-amber-600';
                            $taskDot = 'bg-amber-400';
                            $badge =
                                '<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-400 text-white ml-2">WAJAR</span>';
                        }
                        $uid = 'member_' . $loop->index;
                    @endphp

                    <div class="border-b border-gray-100">

                        {{-- Row --}}
                        <div onclick="toggleTaskDropdown('{{ $uid }}')"
                            class="flex items-center gap-4 px-6 py-4 {{ $rowHover }} transition-colors cursor-pointer select-none">

                            <div
                                class="w-9 h-9 rounded-full {{ $iconBg }} flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-user-clock {{ $iconColor }} text-sm"></i>
                            </div>

                            <div class="flex-1 min-w-0">
                                <p class="text-sm text-gray-800 flex items-center flex-wrap">
                                    <span class="font-semibold text-gray-900">{{ $om['name'] }}</span>
                                    <span class="text-gray-400 mx-1">·</span>
                                    <span class="text-gray-600">memiliki
                                        <span class="font-bold {{ $textBold }}">{{ $om['task_count'] }} task
                                            aktif</span>
                                        melebihi batas wajar
                                    </span>
                                    {!! $badge !!}
                                </p>
                                <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                                    <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $tagBg }}">
                                        <i class="fa-solid fa-folder-open mr-1 text-[10px]"></i>
                                        {{ $om['project'] }}
                                    </span>
                                    <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                                    <span class="text-xs text-gray-400 hint-{{ $uid }}">Klik untuk lihat
                                        task</span>
                                </div>
                            </div>

                            <div class="flex items-center gap-3 flex-shrink-0">
                                <div
                                    class="flex flex-col items-center justify-center w-12 h-12 rounded-full {{ $iconBg }}">
                                    <span
                                        class="text-lg font-extrabold {{ $numColor }}">{{ $om['task_count'] }}</span>
                                    <span class="text-[9px] {{ $iconColor }} font-medium leading-none">tasks</span>
                                </div>
                                <i id="chevron_{{ $uid }}"
                                    class="fa-solid fa-chevron-down text-gray-400 text-xs transition-transform duration-200"></i>
                            </div>
                        </div>

                        {{-- Dropdown --}}
                        <div id="{{ $uid }}" class="hidden bg-gray-50/70 border-t border-gray-100 px-6 py-4">



                            <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 mb-2">
                                Task Aktif ({{ $om['task_count'] }})
                            </p>

                            <div class="flex flex-col gap-1.5">
                                @forelse ($om['tasks'] as $task)
                                    @php $token = $task['token']; @endphp
                                        <a href="{{ route('tasks.show', $token) }}"
                                        class="flex items-start gap-2.5 bg-white rounded-lg px-3 py-2.5 shadow-sm border border-gray-100 hover:border-blue-200 hover:bg-blue-50/30 transition-colors">
                                        <span class="mt-1.5 w-2 h-2 rounded-full {{ $taskDot }} flex-shrink-0"></span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-medium text-gray-800">{{ $task['title'] }}</p>
                                            @if ($task['due_date'])
                                                @php
                                                    $days = $task['days_until_due'];
                                                    if ($days < 0) {
                                                        $dueLabel = 'Terlambat ' . abs($days) . ' hari';
                                                        $dueColor = 'text-red-500';
                                                        $dueIcon = 'fa-circle-exclamation';
                                                    } elseif ($days === 0) {
                                                        $dueLabel = 'Deadline hari ini!';
                                                        $dueColor = 'text-red-500';
                                                        $dueIcon = 'fa-circle-exclamation';
                                                    } elseif ($days <= 3) {
                                                        $dueLabel = $days . ' hari lagi';
                                                        $dueColor = 'text-orange-500';
                                                        $dueIcon = 'fa-clock';
                                                    } elseif ($days <= 7) {
                                                        $dueLabel = $days . ' hari lagi';
                                                        $dueColor = 'text-amber-500';
                                                        $dueIcon = 'fa-clock';
                                                    } else {
                                                        $dueLabel = null; //lebih dari 7 hari, tidak ditampilkan
                                                        $dueColor = '';
                                                        $dueIcon = '';
                                                    }
                                                @endphp

                                                <div class="flex items-center gap-2 mt-0.5 flex-wrap">
                                                    <p class="text-[10px] text-gray-400">
                                                        <i class="fa-regular fa-calendar mr-1"></i>{{ $task['due_date'] }}
                                                    </p>
                                                    @if ($dueLabel)
                                                        <span class="text-[10px] font-semibold {{ $dueColor }}">
                                                            <i
                                                                class="fa-solid {{ $dueIcon }} mr-0.5"></i>{{ $dueLabel }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                        <span
                                            class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 text-gray-500 font-medium flex-shrink-0">
                                            {{ $task['status'] }}
                                        </span>
                                    </a>
                                @empty
                                    <p class="text-xs text-gray-400 italic">Tidak ada data task.</p>
                                @endforelse
                            </div>
                        </div>

                    </div>

                @empty
                    <div class="flex flex-col items-center justify-center py-20 text-center">
                        <div class="w-16 h-16 rounded-full bg-emerald-50 flex items-center justify-center mb-4">
                            <i class="fa-solid fa-circle-check text-emerald-400 text-2xl"></i>
                        </div>
                        <p class="text-sm font-semibold text-gray-600">Tidak ada member overload</p>
                        <p class="text-xs text-gray-400 mt-1">Semua member masih dalam batas beban kerja yang wajar.</p>
                    </div>
                @endforelse

                {{-- Footer info --}}
                @if ($overloadedMembers->count() > 0)
                    <div class="mt-3 flex-shrink-0">
                        <p class="text-xs text-gray-400 text-center">
                            Member dianggap overload jika memiliki 5 atau lebih task aktif (belum selesai / belum
                            dibatalkan)
                        </p>
                    </div>
                @endif

            </div>

            {{-- Modal Info --}}
            <div id="overloadInfoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
                <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

                    <h2 class="text-xl font-semibold mb-3">About member overload</h2>

                    <div class="space-y-4 text-sm text-slate-600">
                        <p>
                            This page monitors each member's workload across projects. Members with
                            too many active tasks are flagged as overloaded.
                        </p>

                        <div class="border-t pt-4 space-y-3">
                            <div class="flex gap-2">
                                <span class="text-slate-800 mt-1">●</span>
                                <p><span class="font-semibold text-slate-800">Overload threshold</span> —
                                    A member is overloaded with 5+ active tasks (not completed or cancelled) in a project.
                                </p>
                            </div>
                            <div class="border-t pt-3 flex gap-2">
                                <span class="text-slate-800 mt-1">●</span>
                                <p><span class="font-semibold text-slate-800">Severity levels</span> —
                                    5–5 tasks is labeled "Wajar", 6–7 "Tinggi", and 8+ "Kritis", each with its own color
                                    badge.</p>
                            </div>
                            <div class="border-t pt-3 flex gap-2">
                                <span class="text-slate-800 mt-1">●</span>
                                <p><span class="font-semibold text-slate-800">Automatic alerts</span> —
                                    Overloaded members trigger a one-time notification and email to relevant users.</p>
                            </div>
                            <div class="border-t pt-3 flex gap-2">
                                <span class="text-slate-800 mt-1">●</span>
                                <p><span class="font-semibold text-slate-800">Expand for details</span> —
                                    Click a member's row to see their active tasks with due dates and status.</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6">
                        <button onclick="closeOverloadInfoModal()"
                            class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                            Done
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <script>
            function toggleTaskDropdown(uid) {
                const dropdown = document.getElementById(uid);
                const chevron = document.getElementById('chevron_' + uid);
                const hint = document.querySelector('.hint-' + uid);
                const isOpen = !dropdown.classList.contains('hidden');

                if (isOpen) {
                    dropdown.classList.add('hidden');
                    chevron.style.transform = 'rotate(0deg)';
                    if (hint) hint.textContent = 'Klik untuk lihat task';
                } else {
                    dropdown.classList.remove('hidden');
                    chevron.style.transform = 'rotate(180deg)';
                    if (hint) hint.textContent = 'Klik untuk tutup';
                }
            }

            function openOverloadInfoModal() {
                document.getElementById('overloadInfoModal').classList.remove('hidden');
                document.getElementById('overloadInfoModal').classList.add('flex');
            }

            function closeOverloadInfoModal() {
                document.getElementById('overloadInfoModal').classList.add('hidden');
                document.getElementById('overloadInfoModal').classList.remove('flex');
            }
        </script>
    @endsection
