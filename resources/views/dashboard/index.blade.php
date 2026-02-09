@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $statusClasses = [
        'to_do' => 'bg-gray-100 text-gray-800',
        'in_progress' => 'bg-blue-100 text-blue-800',
        'review' => 'bg-yellow-100 text-yellow-800',
        'completed' => 'bg-green-100 text-green-800',
        'blocked' => 'bg-red-100 text-red-800',
        'cancelled' => 'bg-gray-100 text-gray-500 line-through',
    ];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"><p class="text-sm text-blue-600">📁 Total Workspaces</p><p class="text-3xl font-bold mt-2">{{ $stats['total_workspaces'] }}</p></div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"><p class="text-sm text-green-600">💼 Total Projects</p><p class="text-3xl font-bold mt-2">{{ $stats['total_projects'] }}</p></div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"><p class="text-sm text-yellow-600">📋 Assigned Tasks</p><p class="text-3xl font-bold mt-2">{{ $stats['assigned_tasks'] }}</p></div>
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"><p class="text-sm text-purple-600">✅ Completed Tasks</p><p class="text-3xl font-bold mt-2">{{ $stats['completed_tasks'] }}</p></div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Recent Tasks</h2>
        @if ($recentTasks->isEmpty())
            <div class="text-center py-12 text-gray-500">No recent tasks found.</div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Task</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Due Date</th>
                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($recentTasks as $task)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $task->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $task->project?->name }}</td>
                                <td class="px-4 py-3"><span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClasses[$task->status] ?? 'bg-gray-100 text-gray-800' }}">{{ str($task->status)->replace('_', ' ')->title() }}</span></td>
                                <td class="px-4 py-3 text-sm text-gray-600">{{ $task->due_date?->format('d M Y') ?? '-' }}</td>
                                <td class="px-4 py-3 text-right"><a href="{{ route('tasks.show', $task) }}" class="text-indigo-600 hover:text-indigo-800 text-sm font-medium">View</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div>
        <h2 class="text-lg font-semibold text-gray-900 mb-4">Active Projects</h2>
        @if ($activeProjects->isEmpty())
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-10 text-center text-gray-500">No active projects available.</div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($activeProjects as $project)
                    @php $projectProgress = $project->calculateProgress(); @endphp
                    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 space-y-3">
                        <a href="{{ route('projects.show', $project) }}" class="font-semibold text-gray-900 hover:text-indigo-600">{{ $project->name }}</a>
                        <p class="text-sm text-gray-500">Workspace: {{ $project->workspace?->name }}</p>
                        <p class="text-sm text-gray-500">{{ $project->tasks_count }} tasks</p>
                        <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $projectProgress }}%"></div></div>
                        <span class="text-sm text-gray-600">{{ round($projectProgress, 1) }}%</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    <div class="fixed bottom-6 right-6 flex flex-col gap-3">
        <a href="{{ route('workspaces.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg shadow">Create Workspace</a>
        <a href="{{ route('projects.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow">Create Project</a>
        <a href="{{ route('tasks.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg shadow">Create Task</a>
    </div>
</div>
@endsection
