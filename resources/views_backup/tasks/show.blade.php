@extends('layouts.app')

@section('title', $task->name)

@section('content')
    @php
        $isManager = $task->project->isManager(Auth::user());
        $canContribute = $task->project->canContribute(Auth::user());

        // Status Badge Colors (Match Expression)
        $statusBadge = match ($task->status) {
            'to_do' => 'bg-amber-100 text-amber-700',
            'in_progress' => 'bg-blue-200 text-blue-800',
            'review' => 'bg-purple-200 text-purple-800',
            'completed' => 'bg-emerald-200 text-emerald-800',
            'stopped' => 'bg-red-200 text-red-800',
            'cancelled' => 'bg-zinc-300 text-zinc-800',
            default => 'bg-slate-200 text-slate-800',
        };

        // Priority Badge Colors (Match Expression)
        $priorityBadge = match ($task->priority) {
            'low' => 'bg-slate-100 text-slate-700',
            'medium' => 'bg-amber-100 text-amber-700',
            'high' => 'bg-orange-200 text-orange-800',
            'urgent' => 'bg-red-200 text-red-800',
            default => 'bg-slate-100 text-slate-700',
        };

        // Progress Percentage based on status
        $progressPercentage = match ($task->status) {
            'to_do' => 10,
            'in_progress' => 50,
            'review' => 80,
            'completed' => 100,
            'stopped' => 0,
            'cancelled' => 0,
            default => 0,
        };

        // Earned Value Calculation
        $weight = $task->weight ?? 1;
        $earnedValue = $task->earned_value ?? round(($progressPercentage / 100) * $weight, 2);

        // Array of color gradients for different users
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

        // Function to get consistent color for each user
        $getUserColor = function ($userId) use ($userColors) {
            if (!$userId) {
                return 'from-gray-400 to-gray-500';
            }
            $index = crc32($userId) % count($userColors);
            return $userColors[abs($index)];
        };

    @endphp

    <div class="bg-gradient-to-br from-gray-50 to-gray-100/50">

        <div class="max-w-7xl mx-auto px-6 py-8 space-y-6">

            {{-- HEADER FULL WIDTH --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm">
                <div class="px-6 py-5">

                    {{-- Breadcrumb --}}
                    <nav class="flex items-center gap-1.5 text-sm mb-4" aria-label="Breadcrumb">
                        <a href="{{ route('workspaces.show', $task->project->workspace) }}"
                            class="inline-flex items-center gap-1.5 text-gray-500 hover:text-indigo-600 transition-colors duration-200">
                            <i class="fa-solid fa-folder-open text-gray-400"></i>
                            <span class="font-medium">{{ $task->project->workspace->name }}</span>
                        </a>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                        <a href="{{ route('projects.show', $task->project) }}"
                            class="inline-flex items-center gap-1.5 text-gray-500 hover:text-indigo-600 transition-colors duration-200">
                            <span class="font-medium">{{ $task->project->name }}</span>
                        </a>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-300"></i>
                        <span class="text-gray-800 font-semibold">{{ $task->name }}</span>
                    </nav>

                    {{-- Title & Actions --}}
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

                        {{-- Title Section --}}
                        <div class="flex-1 min-w-0">
                            <h1 class="text-2xl lg:text-3xl font-bold text-gray-900 tracking-tight truncate mb-3">
                                {{ $task->name }}
                            </h1>

                            {{-- Badges --}}
                            <div class="flex flex-wrap items-center gap-2">
                                {{-- Status Badge --}}
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg {{ $statusBadge }}">
                                    <span class="w-1.5 h-1.5 rounded-full bg-current opacity-75"></span>
                                    {{ str($task->status)->replace('_', ' ')->title() }}
                                </span>

                                {{-- Priority Badge --}}
                                <span
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-lg {{ $priorityBadge }}">
                                    <i class="fa-solid fa-flag text-[10px] opacity-75"></i>
                                    {{ ucfirst($task->priority) }}
                                </span>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center gap-3 flex-shrink-0 lg:ml-auto">
                            @if ($canContribute)
                                <a href="{{ route('tasks.edit', $task) }}"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white
                                bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800
                                rounded-xl shadow-sm hover:shadow-md transition-all duration-200
                                focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    <span>Edit</span>
                                </a>
                            @endif

                            @if ($isManager)
                                <button onclick="openModal('delete-task-modal')"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-red-600
                                bg-red-50 hover:bg-red-100 active:bg-red-200
                                border border-red-200 rounded-xl shadow-sm hover:shadow-md transition-all duration-200
                                focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    <i class="fa-solid fa-trash-can"></i>
                                    <span>Delete</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- GRID 2 KOLOM --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

                {{-- Left Column (Main Content) --}}
                <div class="lg:col-span-2 space-y-6">

                    {{-- Description Card --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fa-regular fa-file-lines text-indigo-500"></i>
                                Description
                            </h2>
                        </div>
                        <div class="p-6">
                            @if ($task->description)
                                <div class="prose prose-sm max-w-none text-gray-700 whitespace-pre-line">
                                    {{ $task->description }}
                                </div>
                            @else
                                <div class="text-gray-400 italic text-sm">
                                    No description provided.
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Task Metadata Grid --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-circle-info text-indigo-500"></i>
                                Task Information
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-8 gap-y-4">
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-indigo-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-regular fa-user text-indigo-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Assignee</p>
                                        @if ($task->assignees->count())
                                            <div class="flex flex-wrap gap-1 mt-0.5">
                                                @foreach ($task->assignees as $assignee)
                                                    <span
                                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                                        {{ $assignee->name }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <p class="text-sm text-gray-400 mt-0.5 italic">Unassigned</p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-person text-emerald-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Created By
                                        </p>
                                        <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                            {{ $task->creator?->name ?? '–' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-regular fa-calendar text-blue-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Start Date
                                        </p>
                                        @if ($task->start_date)
                                            <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                                {{ $task->start_date->format('d M Y') }}
                                            </p>
                                        @else
                                            <p class="text-sm text-gray-400 mt-0.5 italic">
                                                Not set
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex items-start gap-3">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-regular fa-calendar-check text-amber-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Due Date
                                        </p>
                                        @if ($task->due_date)
                                            <p class="text-sm font-semibold text-gray-800 mt-0.5">
                                                {{ $task->due_date->format('d M Y') }}
                                            </p>
                                        @else
                                            <p class="text-sm text-gray-400 mt-0.5 italic">
                                                Not set
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                @if ($task->completed_at)
                                    <div class="flex items-start gap-3 sm:col-span-2">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center flex-shrink-0">
                                            <i class="fa-solid fa-circle-check text-emerald-600 text-sm"></i>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">
                                                Completed
                                            </p>
                                            <p class="text-sm font-semibold text-emerald-700 mt-0.5">
                                                {{ $task->completed_at?->format('d M Y \a\t H:i') }}
                                            </p>
                                        </div>
                                    </div>
                                @endif
                                <div class="flex items-start gap-3 sm:col-span-2 pt-4 border-t border-gray-100">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-purple-50 flex items-center justify-center flex-shrink-0">
                                        <i class="fa-solid fa-scale-balanced text-purple-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Metrics</p>
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

                    {{-- Comments Section --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fa-regular fa-comments text-indigo-500"></i>
                                Comments <span
                                    class="text-sm font-normal text-gray-500">({{ $task->comments->count() }})</span>
                            </h2>
                        </div>
                        <div class="p-6">
                            {{-- Comments List --}}
                            <div class="space-y-4 mb-6">
                                @forelse($task->comments as $comment)
                                    @php
                                        $userColor = $getUserColor($comment->user?->id);
                                    @endphp
                                    <div class="flex gap-3 group">
                                        <div
                                            class="w-9 h-9 rounded-full bg-gradient-to-br {{ $userColor }} flex items-center justify-center text-white text-sm font-semibold flex-shrink-0 shadow-sm">
                                            {{ strtoupper(substr($comment->user?->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div
                                                class="bg-gray-50 rounded-xl rounded-tl-none p-4 border border-gray-100 hover:shadow-md transition-shadow">
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <span class="text-sm font-semibold text-gray-800">
                                                        {{ $comment->user?->name ?? 'Unknown' }}
                                                    </span>
                                                    <span class="text-xs text-gray-400" title="{{ $comment->created_at }}">
                                                        {{ $comment->created_at?->diffForHumans() }}
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-700 whitespace-pre-line break-words">
                                                    {{ $comment->comment }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="text-center py-8">
                                        <div
                                            class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fa-regular fa-comment-slash text-gray-400"></i>
                                        </div>
                                        <p class="text-sm text-gray-500">No comments yet. Be the first to share your
                                            thoughts!</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Comment Form --}}
                            @if ($canContribute)
                                <form method="POST" action="{{ route('tasks.comments.store', $task) }}"
                                    class="border-t border-gray-100 pt-4">
                                    @csrf
                                    <div class="relative">
                                        <textarea name="comment" rows="3"
                                            class="w-full px-4 py-3 pr-12 text-sm border border-gray-200 rounded-xl
                                                   focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                                   transition-all duration-200 resize-none"
                                            placeholder="Write a comment..." required></textarea>
                                        <button type="submit"
                                            class="absolute right-2 bottom-2 p-2 text-indigo-600 hover:text-indigo-700
                                                   hover:bg-indigo-50 rounded-lg transition-colors"
                                            title="Post comment">
                                            <i class="fa-solid fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>

                    {{-- Attachments Section --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-paperclip text-blue-500"></i>
                                Attachments <span
                                    class="text-sm font-normal text-gray-500">({{ $task->attachments->count() }})</span>
                            </h2>
                        </div>
                        <div class="p-6">
                            {{-- Files List --}}
                            <div class="space-y-3 mb-5">
                                @forelse($task->attachments as $file)
                                    <div
                                        class="group flex items-center justify-between p-3 bg-gray-50 hover:bg-gray-100
                                                border border-gray-200 rounded-xl transition-colors">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div
                                                class="w-10 h-10 rounded-lg bg-white border border-gray-200 flex items-center justify-center flex-shrink-0">
                                                <i class="fa-solid fa-file text-gray-400 text-lg"></i>
                                            </div>
                                            <div class="min-w-0">
                                                <p class="text-sm font-medium text-gray-800 truncate">
                                                    {{ $file->file_name }}
                                                </p>
                                                <div class="flex items-center gap-2 mt-0.5">
                                                    <p class="text-xs text-gray-500">
                                                        {{ $file->human_file_size ?? 'N/A' }}</p>
                                                    <span class="text-gray-300">•</span>
                                                    <p class="text-xs text-gray-500">
                                                        by <span
                                                            class="font-medium text-gray-700">{{ $file->uploader?->name ?? ($file->user?->name ?? 'Unknown') }}</span>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('tasks.attachments.download', [$task, $file]) }}"
                                            class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600
                                                   bg-white hover:bg-indigo-50 border border-indigo-200 rounded-lg
                                                   transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                            <i class="fa-solid fa-download"></i>
                                            Download
                                        </a>
                                    </div>
                                @empty
                                    <div class="text-center py-6">
                                        <div
                                            class="w-12 h-12 mx-auto mb-3 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fa-regular fa-folder-open text-gray-400"></i>
                                        </div>
                                        <p class="text-sm text-gray-500">No attachments yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            {{-- Upload Form --}}
                            @if ($canContribute)
                                <form method="POST" action="{{ route('tasks.attachments.store', $task) }}"
                                    enctype="multipart/form-data" class="border-t border-gray-100 pt-4">
                                    @csrf
                                    <div class="space-y-3">
                                        <input name="attachment" type="file"
                                            class="block w-full text-sm text-gray-700
                                                file:mr-4 file:py-2.5 file:px-4
                                                file:rounded-lg file:border-0
                                                file:text-sm file:font-semibold
                                                file:bg-blue-50 file:text-blue-700
                                                hover:file:bg-blue-100
                                                border border-gray-300 rounded-lg
                                                focus:outline-none focus:ring-2 focus:ring-blue-500
                                                cursor-pointer"
                                            required>
                                        <button type="submit"
                                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2 px-4 py-2.5
                                                text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700
                                                rounded-lg transition-colors">
                                            <i class="fa-solid fa-upload"></i>
                                            Upload File
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
                    </div>
                </div>

                {{-- Right Column (Sidebar) --}}
                <div class="space-y-6">

                    {{-- Task Progress Card --}}
                    <div class="bg-gradient-to-br from-[#0096c7] to-[#007aa3] rounded-2xl shadow-lg p-5 text-white">
                        <h3 class="text-sm font-semibold opacity-90 mb-3">Task Progress</h3>
                        <div class="flex items-end justify-between">
                            <div>
                                <p class="text-3xl font-bold">{{ $progressPercentage }}%</p>
                                <p class="text-sm opacity-80">of {{ $weight }} pts completed</p>
                            </div>
                            <div class="text-right">
                                <p class="text-2xl font-bold">{{ number_format($earnedValue, 1) }}</p>
                                <p class="text-xs opacity-80">earned value</p>
                            </div>
                        </div>
                        <div class="mt-4 h-2 bg-white/20 rounded-full overflow-hidden">
                            <div class="h-full bg-white rounded-full transition-all duration-500"
                                style="width: {{ $progressPercentage }}%"></div>
                        </div>
                    </div>

                    {{-- Status History Timeline (NON-STICKY) --}}
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-100 bg-gray-50/50">
                            <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                                <i class="fa-solid fa-clock-rotate-left text-indigo-500"></i>
                                Activity Log
                            </h2>
                        </div>
                        <div class="p-6">
                            <div class="relative">
                                <div class="absolute left-2.5 top-2 bottom-2 w-0.5 bg-gray-200"></div>
                                <div class="space-y-5">
                                    @forelse($task->statusHistory as $history)
                                        <div class="relative pl-8">
                                            <span
                                                class="absolute left-0 top-1 w-5 h-5 rounded-full bg-indigo-100 border-2 border-white
                                                         flex items-center justify-center shadow-sm">
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
                                                    • {{ $history->changed_at?->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    @empty
                                        <div class="relative pl-8">
                                            <span
                                                class="absolute left-0 top-1 w-5 h-5 rounded-full bg-gray-100 border-2 border-white
                                                         flex items-center justify-center">
                                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                            </span>
                                            <p class="text-sm text-gray-500 italic">No activity recorded yet.</p>
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>{{-- end grid --}}
        </div>{{-- end max-w-7xl --}}

        {{-- Delete Confirmation Modal --}}
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
                                <div
                                    class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center flex-shrink-0">
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
                            <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit"
                                    class="inline-flex justify-center px-4 py-2.5 text-sm font-semibold text-white
                               bg-red-600 hover:bg-red-700 rounded-xl transition-colors">
                                    <i class="fa-solid fa-trash-can mr-2"></i>
                                    Yes, Delete
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
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
    </script>
@endsection
