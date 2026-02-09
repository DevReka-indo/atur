@extends('layouts.app')

@section('title', 'Workspaces')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold text-gray-900">Workspaces</h1>
        <a href="{{ route('workspaces.create') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg">Create Workspace</a>
    </div>

    @if ($workspaces->isEmpty())
        <div class="text-center py-12 bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="text-4xl">📁</div>
            <h3 class="mt-2 text-sm font-medium text-gray-900">No workspaces yet</h3>
            <p class="mt-1 text-sm text-gray-500">Create your first workspace!</p>
            <div class="mt-6"><a href="{{ route('workspaces.create') }}" class="inline-flex items-center px-4 py-2 rounded-md text-white bg-indigo-600 hover:bg-indigo-700">Create Workspace</a></div>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($workspaces as $workspace)
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                    <h2 class="font-semibold text-lg text-gray-900">{{ $workspace->name }}</h2>
                    <p class="mt-2 text-sm text-gray-600">{{ \Illuminate\Support\Str::limit($workspace->description, 100) ?: 'No description.' }}</p>
                    <div class="mt-4 text-sm text-gray-500">{{ $workspace->projects_count }} projects • {{ $workspace->members_count }} members</div>
                    <a href="{{ route('workspaces.show', $workspace) }}" class="inline-block mt-4 text-indigo-600 hover:text-indigo-800 font-medium">View</a>
                </div>
            @endforeach
        </div>
    @endif
</div>
@endsection
