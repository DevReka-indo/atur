@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Task</h1>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-3xl">
        <form method="POST" action="{{ route('tasks.update', $task) }}">
            @csrf
            @method('PUT')
            <div class="mb-4"><label for="project_id" class="block text-sm font-medium text-gray-700 mb-2">Project</label><input type="text" value="{{ $project->name }}" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-gray-50" disabled></div>
            <div class="mb-4"><label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label><input type="text" name="name" id="name" value="{{ old('name', $task->name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></div>
            <div class="mb-4"><label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label><textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description', $task->description) }}</textarea></div>
            <div class="mb-4"><label for="assignee_id" class="block text-sm font-medium text-gray-700 mb-2">Assignee</label><select name="assignee_id" id="assignee_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><option value="">Unassigned</option>@foreach($assignees as $assignee)<option value="{{ $assignee->id }}" {{ old('assignee_id', $task->assignee_id) == $assignee->id ? 'selected' : '' }}>{{ $assignee->name }}</option>@endforeach</select></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"><div><label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label><select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">@foreach(['to_do','in_progress','review','completed','blocked','cancelled'] as $status)<option value="{{ $status }}" {{ old('status', $task->status) === $status ? 'selected' : '' }}>{{ str($status)->replace('_',' ')->title() }}</option>@endforeach</select></div><div><label for="priority" class="block text-sm font-medium text-gray-700 mb-2">Priority</label><select name="priority" id="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg">@foreach(['low','medium','high','urgent'] as $priority)<option value="{{ $priority }}" {{ old('priority', $task->priority) === $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>@endforeach</select></div></div>
            <div class="mb-4"><label for="weight" class="block text-sm font-medium text-gray-700 mb-2">Weight</label><input type="number" name="weight" id="weight" step="0.01" min="0.01" value="{{ old('weight', $task->weight) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6"><div><label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label><input type="date" name="start_date" id="start_date" value="{{ old('start_date', optional($task->start_date)->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div><div><label for="due_date" class="block text-sm font-medium text-gray-700 mb-2">Due Date</label><input type="date" name="due_date" id="due_date" value="{{ old('due_date', optional($task->due_date)->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div></div>
            <div class="flex gap-3"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg">Update Task</button><a href="{{ route('tasks.show', $task) }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
