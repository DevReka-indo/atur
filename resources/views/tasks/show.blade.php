@extends('layouts.app')

@section('title', $task->name)

@section('content')
@php
    $isManager = $task->project->isManager(Auth::user());
    $canContribute = $task->project->canContribute(Auth::user());
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ showDeleteModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
        <div>
            <nav class="text-sm text-gray-500 mb-2 flex items-center gap-2" aria-label="Breadcrumb">
                <a href="{{ route('workspaces.show', $task->project->workspace) }}" class="hover:text-indigo-600 hover:underline">
                    {{ $task->project->workspace->name }}
                </a>
                <span aria-hidden="true">/</span>
                <a href="{{ route('projects.show', $task->project) }}" class="hover:text-indigo-600 hover:underline">
                    {{ $task->project->name }}
                </a>
                <span aria-hidden="true">/</span>
                <span class="text-gray-700" aria-current="page">{{ $task->name }}</span>
            </nav>
            <h1 class="text-2xl font-bold text-gray-900">{{ $task->name }}</h1>
            <div class="flex gap-2 mt-2"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ str($task->status)->replace('_',' ')->title() }}</span><span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ ucfirst($task->priority) }}</span></div>
        </div>
        <div class="flex gap-3">
            @if ($canContribute)
                <a
                    href="{{ route('tasks.edit', $task) }}"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg"
                >
                    <i class="fa-solid fa-pen-to-square mr-2"></i>Edit
                </a>
            @endif

            @if ($isManager)
                <button
                    @click="showDeleteModal = true"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg"
                >
                    <i class="fa-solid fa-trash mr-2"></i>Delete
                </button>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-4">
                <h2 class="text-lg font-semibold">Task Details</h2>
                <p class="text-gray-700 whitespace-pre-line">{{ $task->description ?: 'No description provided.' }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                    <p><span class="text-gray-500">Assignee:</span> {{ $task->assignee?->name ?? 'Unassigned' }}</p>
                    <p><span class="text-gray-500">Creator:</span> {{ $task->creator?->name ?? '-' }}</p>
                    <p><span class="text-gray-500">Start:</span> {{ $task->start_date?->format('d M Y') ?? '-' }}</p>
                    <p><span class="text-gray-500">Due:</span> {{ $task->due_date?->format('d M Y') ?? '-' }}</p>
                    <p><span class="text-gray-500">Completed:</span> {{ $task->completed_at?->format('d M Y H:i') ?? '-' }}</p>
                    <p><span class="text-gray-500">Weight:</span> {{ $task->weight }} | Earned Value: {{ number_format($task->earned_value, 2) }}</p>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold mb-4">Subtasks</h2>
                @forelse($task->subtasks as $subtask)
                    <label class="flex items-center gap-2 py-2"><input type="checkbox" disabled {{ $subtask->status === 'completed' ? 'checked' : '' }}><span class="{{ $subtask->status === 'completed' ? 'line-through text-gray-500' : '' }}">{{ $subtask->name }}</span></label>
                @empty
                    <p class="text-sm text-gray-500">No subtasks yet.</p>
                @endforelse
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold mb-4">Comments</h2>
                <div class="space-y-4 mb-4">
                    @forelse($task->comments as $comment)
                        <div class="border border-gray-200 rounded-lg p-3">
                            <p class="text-sm font-medium">{{ $comment->user?->name ?? 'Unknown' }} <span class="text-gray-500 font-normal">• {{ $comment->created_at?->diffForHumans() }}</span></p>
                            <p class="text-sm text-gray-700 mt-1 whitespace-pre-line">{{ $comment->comment }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No comments yet.</p>
                    @endforelse
                </div>
                @if($canContribute)
                <form method="POST" action="{{ route('tasks.comments.store', $task) }}">
                    @csrf
                    <textarea name="comment" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg" placeholder="Add a comment..." required></textarea>
                    <button type="submit" class="mt-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium py-2 px-4 rounded-lg"><i class="fa-solid fa-paper-plane mr-2"></i>Post Comment</button>
                </form>
                @endif
            </div>

            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-semibold mb-4">Attachments</h2>
                <div class="space-y-3 mb-4">
                    @forelse($task->attachments as $file)
                        <div class="flex items-center justify-between border border-gray-200 rounded-lg p-3"><div><p class="text-sm font-medium"><i class="fa-solid fa-paperclip mr-1"></i>{{ $file->file_name }}</p><p class="text-xs text-gray-500">{{ $file->human_file_size }}</p></div><a href="{{ route('tasks.attachments.download', [$task, $file]) }}" class="text-indigo-600 text-sm"><i class="fa-solid fa-download mr-1"></i>Download</a></div>
                    @empty
                        <p class="text-sm text-gray-500">No attachments uploaded.</p>
                    @endforelse
                </div>
                @if($canContribute)
                <form method="POST" action="{{ route('tasks.attachments.store', $task) }}" enctype="multipart/form-data">
                    @csrf
                    <input name="attachment" type="file" class="block w-full text-sm text-gray-700 border border-gray-300 rounded-lg" required>
                    <button type="submit" class="mt-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium py-2 px-4 rounded-lg"><i class="fa-solid fa-upload mr-2"></i>Upload</button>
                </form>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 h-fit">
            <h2 class="text-lg font-semibold mb-4">Status History</h2>
            <div class="space-y-4">
                @forelse($task->statusHistory as $history)
                    <div class="relative pl-6 border-l border-gray-200">
                        <span class="absolute -left-2 top-1 w-3 h-3 bg-indigo-500 rounded-full"></span>
                        <p class="text-sm font-medium">{{ $history->from_status ? str($history->from_status)->replace('_',' ')->title() : 'Created' }} <i class="fa-solid fa-arrow-right-long mx-1 text-xs"></i> {{ str($history->to_status)->replace('_',' ')->title() }}</p>
                        <p class="text-xs text-gray-500">by {{ $history->changer?->name ?? 'System' }} • {{ $history->changed_at?->format('d M Y H:i') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No history available.</p>
                @endforelse
            </div>
        </div>
    </div>

    @if ($isManager)
        <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
            <div class="flex items-center justify-center min-h-screen px-4"><div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg sm:w-full z-10"><div class="px-4 pt-5 pb-4 sm:p-6"><h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3><p class="mt-2 text-sm text-gray-500">Are you sure you want to delete this task?</p></div><div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><form method="POST" action="{{ route('tasks.destroy', $task) }}">@csrf @method('DELETE')<button type="submit" class="rounded-md px-4 py-2 bg-red-600 text-white hover:bg-red-700 sm:ml-3"><i class="fa-solid fa-trash mr-2"></i>Delete</button></form><button @click="showDeleteModal = false" type="button" class="mt-3 sm:mt-0 rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700">Cancel</button></div></div></div>
        </div>
    @endif
</div>
@endsection
