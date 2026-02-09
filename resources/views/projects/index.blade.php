@extends('layouts.app')

@section('title', 'Projects')

@section('content')
@php
    $statuses = ['all' => 'All', 'planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled'];
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ status: 'all' }">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Projects</h1>
        <a href="{{ route('projects.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg">Create Project</a>
    </div>

    <div class="flex flex-wrap gap-2 mb-4">
        @foreach ($statuses as $key => $label)
            <button @click="status = '{{ $key }}'" :class="status === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-300'" class="px-3 py-1.5 rounded-full text-sm font-medium">{{ $label }}</button>
        @endforeach
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50"><tr><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Project</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Workspace</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tasks</th><th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Progress</th><th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th></tr></thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($projects as $project)
                    @php
                        $isManager = $project->isManager(Auth::user());
                        $progress = $project->tasks_count > 0 ? $project->calculateProgress() : 0;
                    @endphp
                    <tr x-show="status === 'all' || status === '{{ $project->status }}'" style="display: none;">
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $project->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $project->workspace?->name }}</td>
                        <td class="px-4 py-3"><span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ str($project->status)->replace('_', ' ')->title() }}</span></td>
                        <td class="px-4 py-3 text-sm text-gray-600">{{ $project->tasks_count }}</td>
                        <td class="px-4 py-3">
                            @if ($project->tasks_count > 0)
                                <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress }}%"></div></div>
                                <span class="text-xs text-gray-600">{{ round($progress, 1) }}%</span>
                            @else
                                <span class="text-xs text-gray-400">No tasks</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <a href="{{ route('projects.show', $project) }}" class="text-indigo-600 text-sm">View</a>
                            @if ($isManager)
                                <a href="{{ route('projects.edit', $project) }}" class="text-gray-700 text-sm">Edit</a>
                                <form method="POST" action="{{ route('projects.destroy', $project) }}" class="inline" onsubmit="return confirm('Delete this project?')">@csrf @method('DELETE')<button class="text-red-600 text-sm">Delete</button></form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No projects found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
