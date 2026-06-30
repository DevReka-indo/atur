@extends('layouts.app')
@section('title', 'Thread Discussion')
@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="w-full px-4">
        {{-- Header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6 flex-shrink-0">
            <div class="flex items-center gap-2">
                <h1 class="text-4xl font-semibold text-slate-900">
                    Thread Discussion
                </h1>

                <button onclick="openDiscussionInfoModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="text-sm text-gray-500 mt-1">Select a project to view discussion topics</p>
        </div>

        <!-- Projects Grid - 3 Cards Per Row -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($projects as $project)
                <a href="{{ route('discussion.show', $project) }}"
                    class="block bg-white rounded-2xl shadow-sm hover:shadow-lg transition-all duration-300 p-6 border border-gray-100 relative">

                    <div class="flex flex-col h-full">

                        <div class="flex items-start gap-4">
                            <div
                                class="w-12 h-12 rounded-lg flex items-center justify-center text-xl font-semibold flex-shrink-0 {{ $project->getInitialColor() }}">
                                {{ strtoupper(substr($project->name, 0, 1)) }}
                            </div>

                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2">
                                    <h3 class="text-lg font-semibold text-gray-900 truncate">
                                        {{ $project->name }}
                                    </h3>
                                    {{-- Dot indicator --}}
                                    @if ($project->unread_total > 0)
                                        <span class="flex-shrink-0 w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 mt-2 text-sm text-gray-500">
                                    <i class="fa-solid fa-layer-group w-4 text-center"></i>
                                    <span class="truncate">
                                        Workspace : {{ $project->workspace->name ?? '-' }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        @php
                            $statusColors = [
                                'planning' => 'bg-gray-100 text-gray-700',
                                'active' => 'bg-emerald-100 text-emerald-800',
                                'on_hold' => 'bg-amber-100 text-amber-800',
                                'completed' => 'bg-blue-100 text-blue-800',
                                'cancelled' => 'bg-red-100 text-red-700',
                                'urgent' => 'bg-orange-200 text-orange-800',
                            ];
                        @endphp

                        <div class="mt-4 flex items-center justify-between">

                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $statusColors[$project->status] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ str_replace('_', ' ', ucfirst($project->status)) }}
                            </span>

                            <div class="flex items-center gap-2">
                                {{-- Unread messages count --}}
                                @if ($project->unread_total > 0)
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-full
                                    text-xs font-semibold bg-red-50 text-red-600 border border-red-200">
                                        <i class="fa-solid fa-envelope text-[10px]"></i>
                                        {{ $project->unread_total }} unread
                                    </span>
                                @endif

                                {{-- Thread count --}}
                                <span
                                    class="w-7 h-7 rounded-full bg-gray-100 text-gray-700 text-xs font-semibold
                                flex items-center justify-center border border-gray-200"
                                    title="{{ $project->threads_count }} threads">
                                    {{ $project->threads_count ?? 0 }}
                                </span>
                            </div>

                        </div>
                    </div>
                </a>
            @empty
                <div class="col-span-3 text-center py-12">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                        </path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No projects</h3>
                    <p class="mt-1 text-sm text-gray-500">Get started by creating a new project.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Modal: Tentang Discussion --}}
    <div id="discussion-info-modal" class="fixed inset-0 bg-black/30 hidden items-center justify-center z-50">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">About thread discussion</h2>

            <div class="space-y-4 text-sm text-slate-600">

                <p>
                    Discussion lets your team chat in threads organized by project, keeping
                    conversations focused and easy to follow.
                </p>

                <div class="border-t pt-4 space-y-3">
                    <div class="flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Unread tracking</span> —
                            Each project and thread shows an unread count based on messages sent since you last opened it.
                        </p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Sorted by activity</span> —
                            Projects and threads with unread messages move to the top of the list.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Manage threads</span> —
                            Project owners, managers, members, or the thread creator can rename or delete a thread.</p>
                    </div>
                    <div class="border-t pt-3 flex gap-2">
                        <span class="text-slate-800 mt-1">●</span>
                        <p><span class="font-semibold text-slate-800">Edit your messages</span> —
                            You can edit or delete only the messages you sent yourself.</p>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-2 mt-6">
                <button onclick="closeDiscussionInfoModal()"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Done
                </button>
            </div>

        </div>
    </div>
    </div>

    @push('scripts')
        <script>
            function openDiscussionInfoModal() {
                document.getElementById('discussion-info-modal').classList.remove('hidden');
                document.getElementById('discussion-info-modal').classList.add('flex');
            }

            function closeDiscussionInfoModal() {
                document.getElementById('discussion-info-modal').classList.add('hidden');
                document.getElementById('discussion-info-modal').classList.remove('flex');
            }

            document.addEventListener('keydown', e => {
                if (e.key === 'Escape') closeDiscussionInfoModal();
            });
        </script>
    @endpush
@endsection
