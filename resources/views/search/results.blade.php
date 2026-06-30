@extends('layouts.app')

@section('title', 'Search Results')

@section('content')

    <h2 class="text-xl font-semibold mb-6">
        Search results for: "{{ $query }}"
    </h2>

    @if ($projects->count())
        <h3 class="text-lg font-semibold mb-2">Projects</h3>
        <div class="space-y-2 mb-6">
            @foreach ($projects as $project)
                <a href="/projects/{{ $project->id }}" class="block p-3 bg-white rounded-lg shadow hover:bg-cyan-50">
                    {{ $project->name }}
                </a>
            @endforeach
        </div>
    @endif

    @if ($tasks->count())
        <h3 class="text-lg font-semibold mb-2">Tasks</h3>
        <div class="space-y-2">
            @foreach ($tasks as $task)
                <a href="/tasks/{{ $task->id }}" class="block p-3 bg-white rounded-lg shadow hover:bg-blue-50">
                    {{ $task->name }}
                </a>
            @endforeach
        </div>
    @endif

    @if (!$projects->count() && !$tasks->count())
        <p class="text-gray-500">No results found.</p>
    @endif

@endsection
