@extends('layouts.app')

@section('title', 'Activity Log')

@section('content')
    <div style="height: calc(100vh - 121px); display: flex; flex-direction: column; overflow: hidden;">
        <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

        {{-- Header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-4xl font-semibold text-slate-900">
                    Activity Log
                </h1>

                <button onclick="openActivityInfoModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">Monitor team activity across projects and workspaces</p>
        </div>

        {{-- Filter --}}
        <div class="flex items-center gap-1 bg-white rounded-lg p-1 overflow-x-auto mb-4 flex-shrink-0 w-fit">
            @php
                $filters = [
                    '' => 'Semua',
                    'task' => 'Task',
                    'project' => 'Project',
                    'workspace' => 'Workspace',
                    'discussion' => 'Discussion',
                    'comment' => 'Komentar',
                    'attachment' => 'Attachment',
                ];
            @endphp
            @foreach ($filters as $value => $label)
                <a href="{{ request()->fullUrlWithQuery(['type' => $value, 'page' => 1]) }}"
                    class="px-4 py-1.5 rounded-md text-sm whitespace-nowrap transition-all
                    {{ request('type', '') === $value
                        ? 'bg-[#ADE8F4] text-gray-900 font-medium shadow-sm'
                        : 'text-gray-500 hover:text-gray-700' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        {{-- Main Card --}}
        <div class="bg-white border border-gray-200 shadow-sm rounded-xl flex-1 overflow-hidden" style="min-height:0;">
            <div style="position:absolute; inset:0; overflow-y:auto; position:relative; height:100%;">

                {{-- Card Header --}}
                <div class="sticky top-0 z-10 flex items-center justify-between px-6 py-3 bg-[#ADE8F4]">
                    <span class="text-xs font-semibold uppercase tracking-wider text-gray-600">
                        {{ $activities->total() }} aktivitas ditemukan
                    </span>
                    <span class="text-xs text-gray-500">
                        Hari ini: <strong class="text-gray-700">{{ $todayCount }}</strong>
                    </span>
                </div>

                {{-- Rows --}}
                @forelse ($activities as $log)
                    @php
                        $entityMap = [
                            'task' => ['label' => 'Task', 'class' => 'bg-purple-100 text-purple-700'],
                            'project' => ['label' => 'Project', 'class' => 'bg-blue-100 text-blue-700'],
                            'workspace' => ['label' => 'Workspace', 'class' => 'bg-emerald-100 text-emerald-700'],
                            'discussion' => ['label' => 'Discussion', 'class' => 'bg-indigo-100 text-indigo-700'],
                            'comment' => ['label' => 'Komentar', 'class' => 'bg-green-100 text-green-700'],
                            'attachment' => ['label' => 'Attachment', 'class' => 'bg-amber-100 text-amber-700'],
                        ];
                        $style = $log->presentation();
                        $entity = $entityMap[$log->entity_type] ?? [
                            'label' => ucfirst($log->entity_type),
                            'class' => 'bg-gray-100 text-gray-600',
                        ];
                        $name = $log->user?->name ?? 'System';
                        $initial = strtoupper(
                            implode('', array_map(fn($w) => $w[0], array_slice(explode(' ', $name), 0, 2))),
                        );
                        $canViewInvitationEmail =
                            $log->entity_type !== 'workspace' ||
                            $managedWorkspaceIds->contains((int) $log->entity_id);
                    @endphp

                    @if ($log->entity_url)
                        <a href="{{ $log->entity_url }}"
                            class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 hover:bg-gray-50 transition-colors cursor-pointer">
                        @else
                            <div
                                class="flex items-start gap-4 px-6 py-4 border-b border-gray-50 hover:bg-gray-50 transition-colors">
                    @endif

                    {{-- Icon --}}
                    <div
                        class="w-9 h-9 rounded-full {{ $style['bg'] }} flex items-center justify-center flex-shrink-0 mt-0.5">
                        <i class="fa-solid {{ $style['icon'] }} {{ $style['color'] }} text-sm"></i>
                    </div>

                    {{-- Content --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-gray-800">
                            <span class="font-semibold text-gray-900">{{ $name }}</span>
                            <span class="text-gray-400 mx-1">·</span>
                            <span class="text-gray-600">{{ $log->displayDescription($canViewInvitationEmail) }}</span>
                        </p>
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            <span class="text-xs text-gray-400 flex items-center gap-1">
                                <i class="fa-regular fa-clock"></i>
                                {{ $log->created_at->diffForHumans() }}
                            </span>
                            <span class="w-1 h-1 rounded-full bg-gray-300"></span>
                            <span class="text-xs px-2 py-0.5 rounded-full font-medium {{ $entity['class'] }}">
                                {{ $entity['label'] }}
                                @if ($log->entity_name)
                                    · {{ $log->entity_name }}
                                @endif
                            </span>
                        </div>
                    </div>

                    {{-- Avatar --}}
                    <div
                        class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xs flex-shrink-0 mt-0.5">
                        {{ $initial }}
                    </div>
                    @if ($log->entity_url)
                        </a>
                    @else
            </div>
            @endif

        @empty
            <div class="flex flex-col items-center justify-center py-20 text-center">
                <div class="w-16 h-16 rounded-full bg-gray-50 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-clock-rotate-left text-gray-300 text-2xl"></i>
                </div>
                <p class="text-sm font-semibold text-gray-600">Belum ada aktivitas</p>
                <p class="text-xs text-gray-400 mt-1">Aktivitas tim akan muncul di sini saat mereka mulai bekerja. </p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- Pagination --}}
    @if ($activities->hasPages())
        <div class="flex items-center justify-between mt-4 flex-shrink-0 flex-wrap gap-2">
            <span class="text-sm text-gray-500">
                Menampilkan
                <strong>{{ $activities->firstItem() }}</strong>–<strong>{{ $activities->lastItem() }}</strong>
                dari <strong>{{ $activities->total() }}</strong> hasil
            </span>
            <div class="flex items-center gap-1">
                @if ($activities->onFirstPage())
                    <span
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-300 text-sm cursor-not-allowed">←</span>
                @else
                    <a href="{{ $activities->previousPageUrl() }}"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm">←</a>
                @endif

                @foreach ($activities->getUrlRange(1, $activities->lastPage()) as $page => $url)
                    @if ($page === $activities->currentPage())
                        <span class="px-3 py-1.5 rounded-lg text-sm font-semibold text-white"
                            style="background:#0096c7;">{{ $page }}</span>
                    @elseif ($page >= $activities->currentPage() - 2 && $page <= $activities->currentPage() + 2)
                        <a href="{{ $url }}"
                            class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm">{{ $page }}</a>
                    @endif
                @endforeach

                @if ($activities->hasMorePages())
                    <a href="{{ $activities->nextPageUrl() }}"
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 text-sm">→</a>
                @else
                    <span
                        class="px-3 py-1.5 rounded-lg border border-gray-200 text-gray-300 text-sm cursor-not-allowed">→</span>
                @endif
            </div>
        </div>
    @endif
    {{-- Modal Info --}}
    <div id="activityInfoModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">About activity log</h2>

            <div class="space-y-4 text-sm text-slate-600">
                <p>
                    This page records team activity across all your projects and workspaces,
                    so you can track who did what and when.
                </p>

                <div class="border-t pt-4 space-y-3">
                    <div class="flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Scoped visibility</span> —
                            Shows your own activity, plus activity from tasks, projects, and workspaces you're part of.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Tracked actions</span> —
                            Logs creating, updating, deleting, status changes, assignments, and comments.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Discussion activity</span> —
                            Creating, renaming, or deleting a discussion topic is recorded here too.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Filter by entity</span> —
                            Narrow the list to Task, Project, Workspace, Discussion, Comment, or Attachment.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Today's count</span> —
                            See how many activities happened today, with results paginated 20 per page.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end mt-6">
                <button onclick="closeActivityInfoModal()"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Done
                </button>
            </div>
        </div>
    </div>

    <script>
        function openActivityInfoModal() {
            document.getElementById('activityInfoModal').classList.remove('hidden');
            document.getElementById('activityInfoModal').classList.add('flex');
        }

        function closeActivityInfoModal() {
            document.getElementById('activityInfoModal').classList.add('hidden');
            document.getElementById('activityInfoModal').classList.remove('flex');
        }
    </script>
    </div>
@endsection
