@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Create Project</h1>
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 max-w-3xl">
        <form method="POST" action="{{ route('projects.store') }}">
            @csrf
            <div class="mb-4">
                <label for="workspace_id" class="block text-sm font-medium text-gray-700 mb-2">Workspace <span class="text-red-500">*</span></label>
                <select name="workspace_id" id="workspace_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('workspace_id') border-red-500 @enderror" required>
                    <option value="">Select workspace</option>
                    @foreach ($workspaces as $workspace)
                        <option value="{{ $workspace->id }}" {{ old('workspace_id', request('workspace_id')) == $workspace->id ? 'selected' : '' }}>{{ $workspace->name }}</option>
                    @endforeach
                </select>
                @error('workspace_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div class="mb-4"><label for="name" class="block text-sm font-medium text-gray-700 mb-2">Name <span class="text-red-500">*</span></label><input type="text" name="name" id="name" value="{{ old('name') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('name') border-red-500 @enderror" required>@error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div class="mb-4"><label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label><textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description') }}</textarea></div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div><label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label><input type="date" name="start_date" id="start_date" value="{{ old('start_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                <div><label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label><input type="date" name="end_date" id="end_date" value="{{ old('end_date') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
            </div>
            <div class="mb-6">
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg @error('status') border-red-500 @enderror">
                    @foreach (['planning','active','on_hold','completed','cancelled'] as $status)
                        <option value="{{ $status }}" {{ old('status', 'planning') === $status ? 'selected' : '' }}>{{ str($status)->replace('_',' ')->title() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex gap-3"><button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg">Create Project</button><a href="{{ route('projects.index') }}" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">Cancel</a></div>
        </form>
    </div>
</div>
@endsection
