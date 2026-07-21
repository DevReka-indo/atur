@extends('layouts.app')

@section('title', request()->routeIs('mytasks.*') ? 'My Task — ' . $task->name : 'Project — ' . $task->project->name . '
    — ' . $task->name)

@section('content')
    @php
        $isManager = $task->project->isManager(Auth::user());
        $canContribute = $task->project->canContribute(Auth::user());

        $statusBadge = match ($task->status) {
            'to_do' => 'bg-amber-100 text-amber-700',
            'in_progress' => 'bg-blue-200 text-blue-800',
            'review' => 'bg-purple-200 text-purple-800',
            'completed' => 'bg-emerald-200 text-emerald-800',
            'stopped' => 'bg-red-200 text-red-800',
            'cancelled' => 'bg-zinc-300 text-zinc-800',
            default => 'bg-slate-200 text-slate-800',
        };

        $priorityBadge = match ($task->priority) {
            'low' => 'bg-slate-100 text-slate-700',
            'medium' => 'bg-amber-100 text-amber-700',
            'high' => 'bg-orange-200 text-orange-800',
            'urgent' => 'bg-red-200 text-red-800',
            default => 'bg-slate-100 text-slate-700',
        };

        $progressPercentage = $hierarchyProgressPercentage;

        $weight = $task->weight ?? 1;
        $earnedValue = $hierarchyEarnedValue;

        $userColors = [
            'from-indigo-400 to-purple-500',
            'from-emerald-400 to-cyan-500',
            'from-orange-400 to-pink-500',
            'from-blue-400 to-indigo-500',
            'from-rose-400 to-red-500',
            'from-amber-400 to-orange-500',
            'from-teal-400 to-emerald-500',
            'from-cyan-400 to-blue-500',
            'from-fuchsia-400 to-purple-500',
            'from-lime-400 to-green-500',
            'from-violet-400 to-purple-500',
            'from-sky-400 to-indigo-500',
        ];

        $getUserColor = function ($userId) use ($userColors) {
            if (!$userId) {
                return 'from-gray-400 to-gray-500';
            }
            $index = crc32($userId) % count($userColors);
            return $userColors[abs($index)];
        };

        $canChangeTaskStatus = $canContribute && !$taskHasSubtasks && (Auth::user()->role ?? 'member') !== 'viewer';
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="max-w-8xl mx-auto px-6 py-8 pt-4 space-y-4">

        {{-- ── HEADER ── --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-6 py-5">

                @include('tasks.partials._breadcrumb', [
                    'task' => $task,
                    'hierarchyAncestors' => $hierarchyAncestors,
                ])

                {{-- Title & Actions --}}
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <div class="flex-1 min-w-0">
                        <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 tracking-tight truncate mb-3">
                            {{ $task->name }}
                        </h1>
                        <div class="flex flex-wrap items-center gap-2">

                            {{-- Status Badge / Dropdown --}}
                            <div class="relative" data-dropdown="task-status">
                                @if ($canChangeTaskStatus)
                                    <button type="button" onclick="toggleStatusDropdown()"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg {{ $statusBadge }} hover:opacity-80 transition-opacity cursor-pointer">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-75"></span>
                                        {{ str($task->status)->replace('_', ' ')->title() }}
                                        <i class="fa-solid fa-chevron-down text-[8px] opacity-60"></i>
                                    </button>
                                    <div id="status-dropdown"
                                        class="hidden absolute z-50 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden py-1">
                                        @foreach (['to_do' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed', 'stopped' => 'Stopped', 'cancelled' => 'Cancelled'] as $value => $label)
                                            <form method="POST" action="{{ route('tasks.updateStatus', $task->token) }}">
                                                @csrf @method('PATCH')
                                                <input type="hidden" name="status" value="{{ $value }}">
                                                <button type="submit"
                                                    class="w-full text-left px-4 py-2.5 text-sm transition-colors flex items-center gap-2
                                                        {{ $task->status === $value ? 'bg-gray-100 font-semibold' : 'hover:bg-gray-50' }}">
                                                    @if ($task->status === $value)
                                                        <i class="fa-solid fa-check text-green-500 text-xs"></i>
                                                    @else
                                                        <span class="w-4"></span>
                                                    @endif
                                                    <span
                                                        class="{{ match ($value) {
                                                            'to_do' => 'text-amber-800',
                                                            'in_progress' => 'text-blue-800',
                                                            'review' => 'text-purple-800',
                                                            'completed' => 'text-emerald-800',
                                                            'stopped' => 'text-red-800',
                                                            'cancelled' => 'text-zinc-800',
                                                            default => 'text-slate-800',
                                                        } }}">{{ $label }}</span>
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @else
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg {{ $statusBadge }} cursor-default">
                                        <span class="w-1.5 h-1.5 rounded-full bg-current opacity-75"></span>
                                        {{ str($task->status)->replace('_', ' ')->title() }}
                                    </span>
                                    @if ($taskHasSubtasks)
                                        <span class="text-xs font-semibold text-indigo-600">Status mengikuti subtask</span>
                                    @endif
                                @endif
                            </div>

                            {{-- Priority Badge --}}
                            <span
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg {{ $priorityBadge }}">
                                <i class="fa-solid fa-flag text-[10px] opacity-75"></i>
                                {{ ucfirst($task->priority) }}
                            </span>
                        </div>
                    </div>

                    {{-- Action Buttons --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        @if ($canAddSubtask)
                            <a href="{{ route('tasks.create', ['parent' => $task->token]) }}"
                                class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-5 py-2.5 text-sm font-semibold text-indigo-700 transition hover:bg-indigo-100">
                                <i class="fa-solid fa-plus"></i> Tambah Subtask
                            </a>
                        @endif
                        @if ($canContribute)
                            <a href="{{ route('tasks.edit', $task->token) }}"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white
                                bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 rounded-xl shadow-sm
                                hover:shadow-md transition-all duration-200 focus:outline-none focus:ring-2
                                focus:ring-indigo-500 focus:ring-offset-2">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </a>
                        @endif
                        @if ($isManager)
                            <button onclick="openModal('delete-task-modal')"
                                class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-red-600
                                bg-red-50 hover:bg-red-100 active:bg-red-200 border border-red-200 rounded-xl
                                shadow-sm hover:shadow-md transition-all duration-200 focus:outline-none
                                focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                <i class="fa-solid fa-trash-can"></i> Delete
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        @include('tasks.partials._hierarchy-summary', [
            'task' => $task,
            'taskDepth' => $taskDepth,
            'taskHasSubtasks' => $taskHasSubtasks,
            'hierarchyProgressPercentage' => $hierarchyProgressPercentage,
        ])

        @include('tasks.partials._subtask-list', [
            'task' => $task,
            'taskHasSubtasks' => $taskHasSubtasks,
            'canAddSubtask' => $canAddSubtask,
            'canContribute' => $canContribute,
        ])

        {{-- ── TAB BAR ── --}}
        <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="px-6 border-b border-gray-100">
                <nav class="flex gap-0 -mb-px py-3" id="task-tabs">
                    @php
                        // Hitung unread count untuk comments & documents
                        $lastSeenComments = session("task_{$task->id}_comments_seen");
                        $lastSeenDocuments = session("task_{$task->id}_documents_seen");

                        $unreadComments = $task->comments
                            ->filter(fn($c) => !$lastSeenComments || $c->created_at > $lastSeenComments)
                            ->count();

                        $unreadDocuments = $task->attachments
                            ->filter(fn($a) => !$lastSeenDocuments || $a->created_at > $lastSeenDocuments)
                            ->count();

                        $tabs = [
                            'detail' => ['label' => 'Detail', 'icon' => 'fa-solid fa-circle-info', 'count' => null],
                            'comments' => [
                                'label' => 'Comments',
                                'icon' => 'fa-regular fa-comments',
                                'count' => $unreadComments,
                                'type' => 'comments',
                            ],
                            'documents' => [
                                'label' => 'Attachment',
                                'icon' => 'fa-solid fa-paperclip',
                                'count' => $unreadDocuments,
                                'type' => 'documents',
                            ],
                            'activity' => [
                                'label' => 'Activity Stream',
                                'icon' => 'fa-solid fa-clock-rotate-left',
                                'count' => null,
                            ],
                        ];
                    @endphp
                    @foreach ($tabs as $key => $tab)
                        <button onclick="switchTab('{{ $key }}')" id="tab-btn-{{ $key }}"
                            data-type="{{ $tab['type'] ?? '' }}" data-task-id="{{ $task->id }}"
                            data-task-token="{{ $task->token }}"
                            class="tab-btn flex items-center gap-1.5 px-4 py-3.5 text-sm font-medium border-b-2 transition-colors whitespace-nowrap
                                {{ $loop->first
                                    ? 'border-indigo-600 text-indigo-600'
                                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                            <i class="{{ $tab['icon'] }} text-[12px]"></i>
                            {{ $tab['label'] }}

                            {{-- Badge hanya muncul jika count > 0 --}}
                            @if ($tab['count'] !== null && $tab['count'] > 0)
                                <span
                                    class="unread-badge inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold rounded-full bg-red-500 text-white transition-all duration-200"
                                    id="badge-{{ $tab['type'] ?? $key }}" data-count="{{ $tab['count'] }}">
                                    {{ $tab['count'] > 9 ? '9+' : $tab['count'] }}
                                </span>
                            @endif
                        </button>
                    @endforeach
                </nav>
            </div>

            <div class="p-6">

                {{-- ══════════════════════════════════
                     TAB: DETAIL
                ══════════════════════════════════ --}}
                <div id="tab-detail" class="tab-content">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                        {{-- Left: Description + Task Info --}}
                        <div class="lg:col-span-2 space-y-6">

                            {{-- Description --}}
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fa-regular fa-file-lines text-indigo-500"></i> Description
                                    </h2>
                                </div>
                                <div class="p-6">
                                    @if ($task->description)
                                        <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line">
                                            {{ $task->description }}
                                        </div>
                                    @else
                                        <p class="text-gray-400 italic text-sm">No description provided.</p>
                                    @endif
                                </div>
                            </div>

                            {{-- Task Information --}}
                            <div class="bg-white rounded-2xl border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                                    <h2 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                                        <i class="fa-solid fa-circle-info text-indigo-500"></i> Task Information
                                    </h2>
                                </div>
                                <div class="p-6">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">

                                        {{-- Assignee --}}
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fa-regular fa-user text-indigo-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                                    PIC</p>
                                                @if ($task->assignees->isNotEmpty())
                                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                                        @foreach ($task->assignees as $assignee)
                                                            @if ($assignee->id === auth()->id())
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-600 text-white">You</span>
                                                            @else
                                                                <span
                                                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $assignee->name }}</span>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                @elseif ($task->assignee)
                                                    <div class="flex flex-wrap gap-1 mt-0.5">
                                                        @if ($task->assignee->id === auth()->id())
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-indigo-600 text-white">You</span>
                                                        @else
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">{{ $task->assignee->name }}</span>
                                                        @endif
                                                    </div>
                                                @else
                                                    <p class="text-sm text-gray-400 mt-0.5 italic">Unassigned</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Created By --}}
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fa-solid fa-person text-emerald-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created
                                                    By</p>
                                                <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                                    {{ $task->creator?->name ?? '–' }}</p>
                                            </div>
                                        </div>

                                        {{-- Start Date --}}
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fa-regular fa-calendar text-blue-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Start
                                                    Date</p>
                                                @if ($task->start_date)
                                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                                        {{ $task->start_date->format('d M Y') }}</p>
                                                @else
                                                    <p class="text-sm text-gray-400 mt-0.5 italic">Not set</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Due Date --}}
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fa-regular fa-calendar-check text-amber-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Due
                                                    Date</p>
                                                @if ($task->due_date)
                                                    <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                                        {{ $task->due_date->format('d M Y') }}</p>
                                                @else
                                                    <p class="text-sm text-gray-400 mt-0.5 italic">Not set</p>
                                                @endif
                                            </div>
                                        </div>

                                        {{-- Completed At --}}
                                        @if ($task->completed_at)
                                            <div class="flex items-start gap-3 sm:col-span-2">
                                                <div
                                                    class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                                    <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                                                </div>
                                                <div>
                                                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                                        Completed</p>
                                                    <p class="text-sm font-semibold text-emerald-700 mt-0.5">
                                                        {{ $task->completed_at->setTimezone(config('app.timezone'))->format('d M Y \a\t H:i') }}
                                                    </p>
                                                </div>
                                            </div>
                                        @endif

                                        {{-- Metrics --}}
                                        <div class="flex items-start gap-3 sm:col-span-2 pt-4 border-t border-gray-100">
                                            <div
                                                class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                                <i class="fa-solid fa-scale-balanced text-purple-600 text-sm"></i>
                                            </div>
                                            <div class="flex-1">
                                                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                                    Metrics</p>
                                                <div class="flex flex-wrap gap-4 mt-1">
                                                    <span class="text-sm font-semibold text-gray-800">
                                                        Weight: <span class="text-indigo-600">{{ $weight }}</span>
                                                    </span>
                                                    <span class="text-sm font-semibold text-gray-800">
                                                        Earned Value: <span
                                                            class="text-emerald-600">{{ number_format($earnedValue, 2) }}</span>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Right: Task Progress --}}
                        <div>
                            <div
                                class="bg-gradient-to-br from-[#0096c7] to-[#007aa3] rounded-2xl shadow-lg p-5 text-white">
                                <h3 class="text-sm font-semibold opacity-90 mb-3">Task Progress</h3>
                                <div class="flex items-end justify-between mb-4">
                                    <div>
                                        <p class="text-3xl font-bold">{{ $progressPercentage }}%</p>
                                        <p class="text-sm opacity-80">of {{ $weight }} pts completed</p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-2xl font-bold">{{ number_format($earnedValue, 1) }}</p>
                                        <p class="text-xs opacity-80">earned value</p>
                                    </div>
                                </div>
                                <div class="h-2 bg-white/20 rounded-full overflow-hidden">
                                    <div class="h-full bg-white rounded-full transition-all duration-500"
                                        style="width: {{ $progressPercentage }}%"></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- ══════════════════════════════════
                    TAB: COMMENTS
                ══════════════════════════════════ --}}
                <div id="tab-comments" class="tab-content hidden">

                    {{-- Scrollable comment area --}}
                    <div class="overflow-y-auto pr-1 mb-6" style="max-height: 420px;">
                        <div class="space-y-5">
                            @forelse($task->comments as $index => $comment)
                                @php
                                    $userColor = $getUserColor($comment->user?->id);
                                    $isMe = $comment->user_id === auth()->id();
                                    $today = now()->startOfDay();
                                    $commentDate = $comment->created_at;

                                    $showSeparator = false;

                                    if ($index === 0) {
                                        $showSeparator = true;
                                    } else {
                                        $prevComment = $task->comments[$index - 1];
                                        if (!$commentDate->isSameDay($prevComment->created_at)) {
                                            $showSeparator = true;
                                        }
                                    }

                                    if ($showSeparator) {
                                        if ($commentDate->isSameDay($today)) {
                                            $dateLabel = 'Today';
                                        } elseif ($commentDate->isSameDay($today->copy()->subDay())) {
                                            $dateLabel = 'Yesterday';
                                        } else {
                                            $dateLabel = $commentDate->format('d M Y');
                                        }
                                    }
                                @endphp

                                {{-- Date separator --}}
                                @if ($showSeparator)
                                    <div class="text-center my-4">
                                        <span
                                            class="text-xs text-gray-400 bg-gray-100 px-3 py-1 rounded-full">{{ $dateLabel }}</span>
                                    </div>
                                @endif

                                <div class="flex items-end gap-2.5 {{ $isMe ? 'justify-end' : 'justify-start' }}">

                                    {{-- Avatar kiri (orang lain) --}}
                                    @if (!$isMe)
                                        <div class="w-8 h-8 rounded-full flex-shrink-0 overflow-hidden">
                                            @if ($comment->user?->profile_photo)
                                                <img src="{{ Storage::url($comment->user->profile_photo) }}"
                                                    alt="{{ $comment->user?->name }}"
                                                    class="w-full h-full object-cover rounded-full"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div
                                                    class="w-8 h-8 bg-gradient-to-br {{ $userColor }} items-center justify-center text-white text-xs font-semibold hidden rounded-full">
                                                    {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                                                </div>
                                            @else
                                                <div
                                                    class="w-8 h-8 bg-gradient-to-br {{ $userColor }} flex items-center justify-center text-white text-xs font-semibold rounded-full">
                                                    {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    {{-- Bubble --}}
                                    <div
                                        class="flex flex-col gap-1 max-w-[65%] {{ $isMe ? 'items-end' : 'items-start' }}">
                                        {{-- Meta: nama saja (tanpa waktu) --}}
                                        <div class="flex items-center gap-2 px-1 {{ $isMe ? 'flex-row-reverse' : '' }}">
                                            <span class="text-xs font-medium text-gray-600">
                                                {{ $isMe ? 'You' : $comment->user?->name ?? 'Unknown' }}
                                            </span>
                                        </div>
                                        {{-- Pesan + jam di dalam bubble --}}
                                        <div
                                            class="px-4 py-2.5 text-sm leading-relaxed break-words
                                            {{ $isMe
                                                ? 'bg-indigo-600 text-white rounded-2xl rounded-br-sm'
                                                : 'bg-gray-200 border border-gray-200 text-gray-700 rounded-2xl rounded-bl-sm shadow-sm' }}">
                                            {{ $comment->comment }}
                                            <div
                                                class="mt-1 text-[10px] {{ $isMe ? 'text-white/60 text-right' : 'text-gray-400 text-left' }}">
                                                {{ $comment->created_at?->setTimezone(config('app.timezone'))->format('H:i') }}
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Avatar kanan (milik sendiri) --}}
                                    @if ($isMe)
                                        <div class="w-8 h-8 rounded-full flex-shrink-0 overflow-hidden">
                                            @if ($comment->user?->profile_photo)
                                                <img src="{{ Storage::url($comment->user->profile_photo) }}"
                                                    alt="{{ $comment->user?->name }}"
                                                    class="w-full h-full object-cover rounded-full"
                                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                <div
                                                    class="w-8 h-8 bg-gradient-to-br {{ $userColor }} items-center justify-center text-white text-xs font-semibold hidden rounded-full">
                                                    {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                                                </div>
                                            @else
                                                <div
                                                    class="w-8 h-8 bg-gradient-to-br {{ $userColor }} flex items-center justify-center text-white text-xs font-semibold rounded-full">
                                                    {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <div
                                        class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fa-regular fa-comment-slash text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500">No comments yet. Be the first to share your thoughts!
                                    </p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @if ($canContribute)
                        <form method="POST" action="{{ route('tasks.comments.store', $task->token) }}"
                            class="border-t border-gray-100 pt-4">
                            @csrf
                            <div class="flex items-end gap-3">
                                <div class="relative flex-1">
                                    <textarea name="comment" rows="2"
                                        class="w-full px-4 py-3 pr-12 text-sm border border-gray-200 rounded-2xl
                                            bg-gray-50 focus:bg-white focus:ring-2 focus:ring-indigo-500
                                            focus:border-indigo-500 transition-all duration-200 resize-none"
                                        placeholder="Write a comment..." required></textarea>
                                </div>
                                <button type="submit"
                                    class="w-10 h-10 flex items-center justify-center bg-indigo-600 hover:bg-indigo-700
                                        text-white rounded-full transition-colors flex-shrink-0 mb-0.5"
                                    title="Post comment">
                                    <i class="fa-solid fa-paper-plane text-sm"></i>
                                </button>
                            </div>
                        </form>
                    @endif

                    {{-- Auto-scroll ke bawah saat dibuka --}}
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const scrollArea = document.querySelector('#tab-comments .overflow-y-auto');
                            if (scrollArea) scrollArea.scrollTop = scrollArea.scrollHeight;
                        });
                    </script>
                </div>

                {{-- ══════════════════════════════════
                     TAB: DOCUMENTS / ATTACHMENTS
                ══════════════════════════════════ --}}
                <div id="tab-documents" class="tab-content hidden">
                    <div class="space-y-3 mb-5">
                        @forelse($task->attachments as $file)
                            <div
                                class="flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100
                                        border border-gray-200 rounded-xl transition-colors">
                                <div class="flex items-center gap-3 min-w-0">
                                    <div
                                        class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-file text-gray-400 text-lg"></i>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $file->file_name }}</p>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <p class="text-xs text-gray-500">{{ $file->human_file_size ?? 'N/A' }}</p>
                                            <span class="text-gray-300">•</span>
                                            <p class="text-xs text-gray-500">
                                                by <span
                                                    class="font-medium text-gray-700">{{ $file->uploader?->name ?? ($file->user?->name ?? 'Unknown') }}</span>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <a href="{{ route('tasks.attachments.download', [$task->token, $file->id]) }}"
                                    class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600
                                           bg-white hover:bg-indigo-50 border border-indigo-200 rounded-lg transition-colors
                                           focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <i class="fa-solid fa-download"></i> Download
                                </a>
                            </div>
                        @empty
                            <div class="text-center py-12">
                                <div
                                    class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                    <i class="fa-regular fa-folder-open text-gray-400"></i>
                                </div>
                                <p class="text-sm text-gray-500">No attachments yet.</p>
                            </div>
                        @endforelse
                    </div>

                    @if ($canContribute)
                        <form method="POST" action="{{ route('tasks.attachments.store', $task->token) }}"
                            enctype="multipart/form-data" class="border-t border-gray-100 pt-4">
                            @csrf
                            <div class="space-y-3">
                                <input name="attachment" type="file"
                                    class="block w-full text-sm text-gray-700
                                        file:mr-4 file:py-2.5 file:px-4 file:rounded-lg file:border-0
                                        file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700
                                        hover:file:bg-blue-100 border border-gray-300 rounded-lg
                                        focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer"
                                    required>
                                <button type="submit"
                                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5
                                        text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                    <i class="fa-solid fa-upload"></i> Upload File
                                </button>
                            </div>
                            @error('attachment')
                                <p class="mt-2 text-sm text-red-600 flex items-center gap-1">
                                    <i class="fa-solid fa-circle-exclamation"></i> {{ $message }}
                                </p>
                            @enderror
                        </form>
                    @endif
                </div>

                {{-- ══════════════════════════════════
                     TAB: ACTIVITY STREAM
                ══════════════════════════════════ --}}
                <div id="tab-activity" class="tab-content hidden">
                    <div class="relative">
                        <div class="absolute left-2.5 top-2 bottom-2 w-0.5 bg-gray-200"></div>
                        <div class="space-y-4">
                            @forelse($task->statusHistory as $history)
                                <div class="relative pl-8">
                                    <span
                                        class="absolute left-0 top-1 w-5 h-5 rounded-full bg-indigo-100 border-2 border-white flex items-center justify-center shadow-sm">
                                        <span class="w-2 h-2 rounded-full bg-indigo-500"></span>
                                    </span>
                                    <div class="bg-gray-50 rounded-xl p-3 border border-gray-100">
                                        <p class="text-sm font-medium text-gray-800">
                                            {{ $history->from_status ? str($history->from_status)->replace('_', ' ')->title() : 'Created' }}
                                            <i class="fa-solid fa-arrow-right-long mx-1 text-xs text-gray-400"></i>
                                            <span
                                                class="text-indigo-600">{{ str($history->to_status)->replace('_', ' ')->title() }}</span>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">
                                            by <span
                                                class="font-medium text-gray-700">{{ $history->changer?->name ?? 'System' }}</span>
                                            •
                                            {{ $history->changed_at?->setTimezone(config('app.timezone'))->format('d M Y, H:i') }}
                                        </p>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-12">
                                    <div
                                        class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fa-solid fa-clock-rotate-left text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500 italic">No activity recorded yet.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>{{-- /p-6 --}}
        </div>{{-- /tab panel card --}}

    </div>{{-- /max-w-8xl --}}

    {{-- ── DELETE MODAL ── --}}
    @if ($isManager)
        <div id="delete-task-modal" class="hidden fixed inset-0 z-50 overflow-y-auto" style="display:none;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm" onclick="closeModal('delete-task-modal')">
                </div>
                <div
                    class="inline-block align-bottom bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden
                    transform transition-all sm:my-8 sm:align-middle">
                    <div class="px-6 py-4 border-b border-gray-100 bg-red-50/50">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
                                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900">Delete Task?</h3>
                        </div>
                    </div>
                    <div class="px-6 py-4">
                        <p class="text-sm text-gray-600">
                            Are you sure you want to delete
                            <span class="font-semibold text-gray-900">"{{ $task->name }}"</span>?
                            This action cannot be undone and will permanently remove all associated data.
                        </p>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
                        <button onclick="closeModal('delete-task-modal')" type="button"
                            class="inline-flex justify-center px-4 py-2.5 text-sm font-semibold text-gray-700
                            bg-white hover:bg-gray-50 border border-gray-300 rounded-xl transition-colors">
                            Cancel
                        </button>
                        <form method="POST" action="{{ route('tasks.destroy', $task->token) }}" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit"
                                class="inline-flex justify-center px-4 py-2.5 text-sm font-semibold text-white
                                bg-red-600 hover:bg-red-700 rounded-xl transition-colors">
                                <i class="fa-solid fa-trash-can mr-2"></i> Yes, Delete
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <script>
        function switchTab(name) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('border-indigo-600', 'text-indigo-600');
                btn.classList.add('border-transparent', 'text-gray-500');
            });

            document.getElementById('tab-' + name).classList.remove('hidden');

            const activeBtn = document.getElementById('tab-btn-' + name);
            activeBtn.classList.remove('border-transparent', 'text-gray-500');
            activeBtn.classList.add('border-indigo-600', 'text-indigo-600');

            history.replaceState(null, '', '#' + name);

            if (['comments', 'documents'].includes(name)) {
                const type = activeBtn?.dataset?.type;
                const taskToken = activeBtn?.dataset?.taskToken;
                if (type && taskToken) {
                    markTabRead(type, taskToken);
                }
            }
        }

        function markTabRead(type, taskToken) {
            const badge = document.querySelector(`#tab-btn-${type} .unread-badge`);
            if (badge) {
                badge.style.opacity = '0';
                badge.style.transform = 'scale(0.8)';
                setTimeout(() => badge.remove(), 200);
            }

            fetch(`/tasks/${taskToken}/mark-seen`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content
                },
                body: JSON.stringify({
                    type
                })
            }).catch(console.error);
        }

        document.addEventListener('DOMContentLoaded', function() {
            const hash = window.location.hash.replace('#', '');
            const validTabs = ['detail', 'comments', 'documents', 'issues', 'activity'];
            if (hash && validTabs.includes(hash)) {
                switchTab(hash);
            }

            // ✅ FIX: Inject hash ke action semua form sebelum di-submit
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    const currentHash = window.location.hash.replace('#', '');
                    if (!currentHash) return;

                    const action = form.getAttribute('action');
                    if (action && !action.includes('#')) {
                        // Untuk form biasa, tambah ?tab=xxx agar backend bisa redirect ke sana
                        // atau gunakan hidden input untuk dikirim ke server
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = '_tab';
                        input.value = currentHash;
                        form.appendChild(input);
                    }
                });
            });

            // ✅ FIX: Auto-scroll comment ke bawah
            const scrollArea = document.querySelector('#tab-comments .overflow-y-auto');
            if (scrollArea) scrollArea.scrollTop = scrollArea.scrollHeight;
        });

        // Modal
        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.remove('hidden');
                modal.style.display = 'block';
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) {
                modal.classList.add('hidden');
                modal.style.display = 'none';
            }
        }

        // Status dropdown
        function toggleStatusDropdown() {
            document.getElementById('status-dropdown').classList.toggle('hidden');
        }

        document.addEventListener('click', function(e) {
            const container = e.target.closest('[data-dropdown="task-status"]');
            const dropdown = document.getElementById('status-dropdown');
            if (!container && dropdown && !dropdown.classList.contains('hidden')) {
                dropdown.classList.add('hidden');
            }
        });
    </script>

@endsection
