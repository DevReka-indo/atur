@extends('layouts.app')

@section('title', 'My Tasks')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ status: 'all', priority: 'all' }">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">My Tasks</h1>

    <div class="flex flex-wrap gap-3 mb-4">
        <select x-model="status" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="all">All Status</option>
            @foreach (['to_do','in_progress','review','completed','blocked','cancelled'] as $status)
                <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
            @endforeach
        </select>
        <select x-model="priority" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="all">All Priority</option>
            @foreach (['low','medium','high','urgent'] as $prio)
                <option value="{{ $prio }}">{{ ucfirst($prio) }}</option>
            @endforeach
        </select>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3"></th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Priority</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($tasks as $task)
                    <tr x-show="(status === 'all' || status === '{{ $task->status }}') && (priority === 'all' || priority === '{{ $task->priority }}')">
                        <td class="px-4 py-3"><input type="checkbox" disabled {{ $task->status === 'completed' ? 'checked' : '' }}></td>
                        <td class="px-4 py-3 font-medium {{ $task->status === 'completed' ? 'line-through text-gray-500' : 'text-gray-900' }}">{{ $task->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $task->project?->name }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ str($task->status)->replace('_', ' ')->title() }}</span></td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded bg-gray-100 text-gray-700">{{ ucfirst($task->priority) }}</span></td>
                        <td class="px-4 py-3 text-sm {{ $task->isOverdue() ? 'text-red-600 font-medium' : 'text-gray-600' }}">{{ $task->due_date?->format('d M Y') ?? '-' }}</td>
                        <td class="px-4 py-3 text-right"><a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 text-sm mr-2">View</a><a href="{{ route('tasks.edit', $task) }}" class="text-gray-700 text-sm">Edit</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-gray-500">No tasks found for your projects yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
