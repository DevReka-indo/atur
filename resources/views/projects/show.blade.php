@extends('layouts.app')

@section('title', 'Project — ' . $project->name)

@section('content')
    <div
        class="fixed inset-0 -z-10 bg-gradient-to-br
            from-gray-50 to-gray-100/50"
    ></div>

    @include('projects.partials.show._header')

    <div class="mx-auto max-w-8xl pb-8">
        <div class="rounded-xl border border-gray-200 bg-white shadow-sm">
            @include('projects.partials.show._tab-bar')

            @include('projects.partials.show._tasks-tab')

            @include('projects.partials.show._members-tab')

            @include('projects.partials.show._chart-tab')
        </div>
    </div>

    @include('projects.partials.show._delete-modal')

    @include('projects.partials.show._task-status-dropdown')

    @push('scripts')
        @include('projects.partials.show._scripts')
    @endpush
@endsection
