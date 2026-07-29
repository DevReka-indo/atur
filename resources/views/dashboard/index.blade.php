@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    @include('dashboard.partials._header')

    @include('dashboard.partials._stats-cards', [
        'stats' => $stats,
    ])

    @include('dashboard.partials._urgent-alert', [
        'projectStats' => $projectStats,
    ])

    @include('dashboard.partials._workload-link')

    <div id="dashboard-grid" class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        @include('dashboard.partials._project-status-chart', [
            'projectStats' => $projectStats,
        ])

        @include('dashboard.partials._deadline-tasks', [
            'deadlineTasks' => $deadlineTasks,
        ])

        @include('dashboard.partials._recent-tasks', [
            'recentTasks' => $recentTasks,
        ])

        @include('dashboard.partials._active-projects', [
            'activeProjects' => $activeProjects,
        ])
    </div>

    @include('dashboard.partials._floating-action-button')

    @include('dashboard.partials._scripts', [
        'projectStats' => $projectStats,
    ])

    @include('dashboard.partials._styles')
@endsection
