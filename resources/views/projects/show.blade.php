@extends('layouts.app')

@section('title', $project->name)

@section('content')
@php
    $isManager = $project->isManager(Auth::user());
    $totalTasks = $project->tasks->count();
    $completedTasks = $project->tasks->where('status', 'completed')->count();
    $overdueTasks = $project->tasks->filter(fn($task) => $task->isOverdue())->count();
    $groups = ['to_do' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed'];
    $canManageMembers = $project->isManager(Auth::user());
    $canContribute = $project->canContribute(Auth::user());
@endphp
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ tab: 'tasks', showDeleteModal: false, openStatus: {to_do: true, in_progress: true, review: true, completed: true} }">
    <div class="mb-6">
        <p class="text-sm text-gray-500 mb-2">{{ $project->workspace->name }} > {{ $project->name }}</p>
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">{{ $project->name }}</h1>
                <div class="flex items-center gap-3 mt-2">
                    <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-800">{{ str($project->status)->replace('_', ' ')->title() }}</span>
                    <span class="text-sm text-gray-500">{{ $project->start_date?->format('d M Y') ?? '-' }} - {{ $project->end_date?->format('d M Y') ?? '-' }}</span>
                </div>
            </div>
            @if ($isManager)
                <div class="flex gap-3"><a href="{{ route('projects.edit', $project) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg"><i class="fa-solid fa-pen-to-square mr-2"></i>Edit</a><button @click="showDeleteModal = true" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg"><i class="fa-solid fa-trash mr-2"></i>Delete</button></div>
            @endif
        </div>
        <div class="mt-4">
            <div class="w-full bg-gray-200 rounded-full h-2"><div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $progress }}%"></div></div>
            <span class="text-sm text-gray-600 mt-1">{{ round($progress, 1) }}%</span>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 p-4"><p class="text-sm text-gray-500">Total Tasks</p><p class="text-2xl font-bold">{{ $totalTasks }}</p></div>
        <div class="bg-white rounded-lg border border-gray-200 p-4"><p class="text-sm text-gray-500">Completed Tasks</p><p class="text-2xl font-bold text-green-600">{{ $completedTasks }}</p></div>
        <div class="bg-white rounded-lg border border-gray-200 p-4"><p class="text-sm text-gray-500">Overdue Tasks</p><p class="text-2xl font-bold text-red-600">{{ $overdueTasks }}</p></div>
        <div class="bg-white rounded-lg border border-gray-200 p-4"><p class="text-sm text-gray-500">Progress</p><p class="text-2xl font-bold text-indigo-600">{{ round($progress, 1) }}%</p></div>
    </div>

    <div class="border-b border-gray-200 mb-6"><nav class="-mb-px flex space-x-8"><button @click="tab = 'tasks'" :class="tab === 'tasks' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500'" class="py-3 px-1 border-b-2 text-sm font-medium">Tasks</button><button @click="tab = 'members'" :class="tab === 'members' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500'" class="py-3 px-1 border-b-2 text-sm font-medium">Members</button><button @click="tab = 'chart'" :class="tab === 'chart' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500'" class="py-3 px-1 border-b-2 text-sm font-medium">Progress Chart</button></nav></div>

    <div x-show="tab === 'tasks'" style="display: none;" class="space-y-4">
        @if($canContribute)
        <a href="{{ route('tasks.create') }}?project_id={{ $project->id }}" class="inline-flex bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg"><i class="fa-solid fa-plus mr-2"></i>Create Task</a>
        @endif
        @foreach ($groups as $statusKey => $statusLabel)
            @php $tasks = $project->tasks->where('status', $statusKey); @endphp
            <div class="bg-white rounded-lg border border-gray-200">
                <button @click="openStatus.{{ $statusKey }} = !openStatus.{{ $statusKey }}" class="w-full px-4 py-3 flex items-center justify-between text-left"><span class="font-semibold">{{ $statusLabel }} ({{ $tasks->count() }})</span><i :class="openStatus.{{ $statusKey }} ? 'fa-solid fa-minus' : 'fa-solid fa-plus'"></i></button>
                <div x-show="openStatus.{{ $statusKey }}" style="display: none;" class="border-t border-gray-200 p-4 space-y-3">
                    @forelse ($tasks as $task)
                        <div class="p-4 rounded-lg border border-gray-200 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-3">
                            <div class="space-y-1">
                                <div class="flex items-center gap-2"><input type="checkbox" disabled {{ $task->status === 'completed' ? 'checked' : '' }}><p class="font-medium {{ $task->status === 'completed' ? 'line-through text-gray-500' : 'text-gray-900' }}">{{ $task->name }}</p></div>
                                <p class="text-sm text-gray-500">Assignee: {{ $task->assignee?->name ?? 'Unassigned' }}</p>
                                <div class="flex flex-wrap gap-2 text-xs"><span class="px-2 py-1 rounded-full bg-blue-100 text-blue-800">{{ str($task->status)->replace('_', ' ')->title() }}</span><span class="px-2 py-1 rounded bg-gray-100 text-gray-700">{{ ucfirst($task->priority) }}</span><span class="text-gray-500">Weight: {{ $task->weight }}</span></div>
                                <p class="text-sm {{ $task->isOverdue() ? 'text-red-600' : 'text-gray-500' }}">Due: {{ $task->due_date?->format('d M Y') ?? '-' }}</p>
                            </div>
                            <div class="flex gap-3 text-sm">
                                <a href="{{ route('tasks.show', $task) }}" class="text-indigo-600"><i class="fa-solid fa-eye mr-1"></i>View</a>
                                @if($canContribute)
                                    <a href="{{ route('tasks.edit', $task) }}" class="text-gray-700"><i class="fa-solid fa-pen-to-square mr-2"></i>Edit</a>
                                @endif
                                @if($isManager)
                                    <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="return confirm('Delete this task?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="text-red-600"><i class="fa-solid fa-trash mr-2"></i>Delete</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No tasks in this status.</p>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <div x-show="tab === 'members'" style="display: none;" class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="font-semibold">Project Members</h3></div>
        @if($canManageMembers)
            <form method="POST" action="{{ route('projects.members.store', $project) }}" class="mb-4 grid grid-cols-1 md:grid-cols-3 gap-3">
                @csrf
                <select name="user_id" class="px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="">Select workspace member</option>
                    @foreach($availableMembers as $candidate)
                        <option value="{{ $candidate->id }}">{{ $candidate->name }}</option>
                    @endforeach
                </select>
                <select name="role" class="px-3 py-2 border border-gray-300 rounded-lg" required>
                    <option value="member">Member</option>
                    <option value="manager">Manager</option>
                    <option value="viewer">Viewer</option>
                </select>
                <button class="bg-indigo-600 text-white rounded-lg px-4 py-2"><i class="fa-solid fa-user-plus mr-2"></i>Add Member</button>
            </form>
        @endif
        <div class="space-y-3">
            @foreach($project->members as $member)
            <div class="flex items-center justify-between border border-gray-200 rounded-lg p-3">
                <div class="flex items-center gap-3"><div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center font-semibold">{{ strtoupper(substr($member->name,0,1)) }}</div><div><p class="font-medium">{{ $member->name }}</p><p class="text-xs text-gray-500">{{ $member->job_title ?: '-' }}</p></div></div>
                @if($canManageMembers)
                <div class="flex items-center gap-2">
                    <form method="POST" action="{{ route('projects.members.update', [$project, $member]) }}" class="flex items-center gap-2">
                        @csrf
                        @method('PATCH')
                        <select name="role" class="px-2 py-1 border border-gray-300 rounded text-sm">
                            <option value="manager" {{ $member->pivot->role === 'manager' ? 'selected' : '' }}>Manager</option>
                            <option value="member" {{ $member->pivot->role === 'member' ? 'selected' : '' }}>Member</option>
                            <option value="viewer" {{ $member->pivot->role === 'viewer' ? 'selected' : '' }}>Viewer</option>
                        </select>
                        <button class="text-indigo-600 text-sm"><i class="fa-solid fa-floppy-disk mr-1"></i>Save</button>
                    </form>
                    <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}">
                        @csrf
                        @method('DELETE')
                        <button class="text-red-600 text-sm"><i class="fa-solid fa-user-minus mr-1"></i>Remove</button>
                    </form>
                </div>
                @else
                <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">{{ ucfirst($member->pivot->role) }}</span>
                @endif
            </div>
            @endforeach
        </div>
    </div>

    <div x-show="tab === 'chart'" style="display: none;" class="bg-white rounded-lg border border-gray-200 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="font-semibold text-gray-900">S-Curve: Planned vs Actual</h3>
            <span class="text-sm text-gray-500">Baseline: {{ $baseline?->baseline_name ?? 'No active baseline' }}</span>
        </div>

        @if (empty($chartData['labels']))
            <div class="p-10 text-center text-gray-500">No progress data available yet for chart visualization.</div>
        @else
            <div class="h-[32rem]">
                <canvas id="projectProgressChart"></canvas>
            </div>
        @endif
    </div>

    <div x-show="showDeleteModal" class="fixed inset-0 z-50 overflow-y-auto" style="display:none;">
        <div class="flex items-center justify-center min-h-screen px-4"><div class="fixed inset-0 bg-gray-500 bg-opacity-75"></div><div class="bg-white rounded-lg overflow-hidden shadow-xl sm:max-w-lg sm:w-full z-10"><div class="px-4 pt-5 pb-4 sm:p-6"><h3 class="text-lg font-medium text-gray-900">Confirm Delete</h3><p class="mt-2 text-sm text-gray-500">Are you sure you want to delete this project?</p></div><div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse"><form method="POST" action="{{ route('projects.destroy', $project) }}">@csrf @method('DELETE')<button type="submit" class="rounded-md px-4 py-2 bg-red-600 text-white hover:bg-red-700 sm:ml-3"><i class="fa-solid fa-trash mr-2"></i>Delete</button></form><button @click="showDeleteModal = false" type="button" class="mt-3 sm:mt-0 rounded-md border border-gray-300 px-4 py-2 bg-white text-gray-700">Cancel</button></div></div></div>
    </div>
</div>
@endsection


@push('scripts')
@if (!empty($chartData['labels']))
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const chartEl = document.getElementById('projectProgressChart');
        if (!chartEl) return;

        const rawData = @json($chartData);

        // Extend actual progress line to the end of timeline (forward-fill last known value)
        const actualExtended = [];
        let lastActual = null;
        for (const value of rawData.actual) {
            if (value !== null && value !== undefined) {
                lastActual = value;
                actualExtended.push(value);
            } else {
                actualExtended.push(lastActual);
            }
        }

        const maxValue = Math.max(
            100,
            ...rawData.planned.filter(v => v !== null && v !== undefined),
            ...actualExtended.filter(v => v !== null && v !== undefined)
        );

        new Chart(chartEl, {
            type: 'line',
            data: {
                labels: rawData.labels,
                datasets: [
                    {
                        label: 'Planned (%)',
                        data: rawData.planned,
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.2)',
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        spanGaps: true,
                    },
                    {
                        label: 'Actual (%)',
                        data: actualExtended,
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.2)',
                        borderWidth: 2,
                        tension: 0.25,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        spanGaps: true,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        ticks: {
                            maxRotation: 0,
                            autoSkip: true,
                            maxTicksLimit: 12
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.2)'
                        }
                    },
                    y: {
                        min: 0,
                        max: Math.ceil(maxValue / 25) * 25 + 25,
                        ticks: {
                            stepSize: 25,
                            callback: function(value) { return value + '%'; }
                        },
                        grid: {
                            color: 'rgba(148, 163, 184, 0.25)'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                if (context.parsed.y === null) return `${context.dataset.label}: -`;
                                return `${context.dataset.label}: ${context.parsed.y}%`;
                            }
                        }
                    }
                }
            }
        });
    });
</script>
@endif
@endpush
