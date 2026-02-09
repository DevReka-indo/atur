@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Edit Project</h1>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-3xl">
        <form method="POST" action="{{ route('projects.update', $project) }}">
            @csrf
            @method('PUT')
            <div class="mb-4"><label for="workspace_id" class="block text-sm font-medium text-gray-700 mb-2">Workspace</label><select name="workspace_id" id="workspace_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">@foreach ($workspaces as $workspace)<option value="{{ $workspace->id }}" {{ old('workspace_id', $project->workspace_id) == $workspace->id ? 'selected' : '' }}>{{ $workspace->name }}</option>@endforeach</select></div>
            <div class="mb-4"><label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name</label><input type="text" name="name" id="name" value="{{ old('name', $project->name) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></div>
            <div class="mb-4"><label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label><textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description', $project->description) }}</textarea></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4"><div><label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label><input type="date" name="start_date" id="start_date" value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div><div><label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label><input type="date" name="end_date" id="end_date" value="{{ old('end_date', optional($project->end_date)->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div></div>
            <div class="mb-6"><label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label><select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">@foreach (['planning','active','on_hold','completed','cancelled'] as $status)<option value="{{ $status }}" {{ old('status', $project->status) === $status ? 'selected' : '' }}>{{ str($status)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
            <div class="flex gap-3"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg">Update Project</button><a href="{{ route('projects.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
