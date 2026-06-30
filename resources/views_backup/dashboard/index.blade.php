@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $statusConfig = [
            'to_do' => [
                'class' => 'bg-gray-100 text-gray-700 border-gray-200',
                'icon' => 'fa-circle-dot',
                'label' => 'To Do',
            ],
            'in_progress' => [
                'class' => 'bg-blue-50 text-blue-700 border-blue-200',
                'icon' => 'fa-spinner',
                'label' => 'In Progress',
            ],
            'review' => [
                'class' => 'bg-amber-50 text-amber-700 border-amber-200',
                'icon' => 'fa-eye',
                'label' => 'Review',
            ],
            'completed' => [
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                'icon' => 'fa-circle-check',
                'label' => 'Completed',
            ],
            'blocked' => [
                'class' => 'bg-red-50 text-red-700 border-red-200',
                'icon' => 'fa-circle-exclamation',
                'label' => 'Blocked',
            ],
            'cancelled' => [
                'class' => 'bg-gray-50 text-gray-400 border-gray-200 line-through',
                'icon' => 'fa-circle-xmark',
                'label' => 'Cancelled',
            ],
        ];
    @endphp

    {{-- Header Section --}}
    <div class="mb-2 px-4 sm:px-6 lg:py-6">
        <div class="flex flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Welcome, {{ Auth::user()->name }}!</h1>
                <p class="text-gray-500 mt-1">Here's what's happening with your projects today</p>
            </div>
            <div>
                <span class="text-sm text-gray-500 bg-gray-100 px-3 py-1.5 rounded-full">
                    <i class="fa-regular fa-calendar mr-1"></i>
                    {{ now()->format('l, d F Y') }}
                </span>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6 px-4 sm:px-6">
        <a href="{{ route('projects.index') }}"
            class="block bg-white rounded-xl border-t-4 border-blue-500 shadow-sm px-5 py-5
            hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Overall Projects</p>
                <div class="p-2 bg-blue-50 rounded-lg">
                    <i class="fa-solid fa-folder-tree text-blue-600 text-lg"></i>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $stats['total_projects'] }}</p>
            <p class="text-xs text-blue-600 font-medium flex items-center gap-1">
                <i class="fa-solid fa-arrow-trend-up text-[10px]"></i> Across all workspaces
            </p>
        </a>

        <a href="{{ route('workspaces.index') }}"
            class="block bg-white rounded-xl border-t-4 border-emerald-500 shadow-sm px-5 py-5
            hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Active Workspaces</p>
                <div class="p-2 bg-emerald-50 rounded-lg">
                    <i class="fa-solid fa-layer-group text-emerald-600 text-lg"></i>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $stats['total_workspaces'] }}</p>
            <p class="text-xs text-emerald-600 font-medium">On going</p>
        </a>

        <a href="{{ route('tasks.index') }}"
            class="block bg-white rounded-xl border-t-4 border-violet-500 shadow-sm px-5 py-5
            hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Completed</p>
                <div class="p-2 bg-violet-50 rounded-lg">
                    <i class="fa-solid fa-clipboard-check text-violet-600 text-lg"></i>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $stats['completed_tasks'] }}</p>
            @php
                $completionRate =
                    $stats['assigned_tasks'] > 0
                        ? round(($stats['completed_tasks'] / $stats['assigned_tasks']) * 100)
                        : 0;
            @endphp
            <p class="text-xs text-violet-600 font-medium flex items-center gap-1">
                <i class="fa-solid text-[10px]"></i> {{ $completionRate }}% completion rate
            </p>
        </a>

        <a href="{{ route('tasks.index') }}"
            class="block bg-white rounded-xl border-t-4 border-amber-500 shadow-sm px-5 py-5
            hover:shadow-lg hover:-translate-y-1 transition-all duration-300 cursor-pointer">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Assigned Tasks</p>
                <div class="p-2 bg-amber-50 rounded-lg">
                    <i class="fa-solid fa-clock text-amber-600 text-lg"></i>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $stats['assigned_tasks'] }}</p>
            <p class="text-xs text-amber-600 font-medium">Attention Required</p>
        </a>
    </div>

    {{-- GRID WIDGETS --}}
    <div class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Widget 1: Pie Chart --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200/60 overflow-visible flex flex-col"
            style="height:420px;">

            {{-- Header --}}
            <div
                class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent">
                <div class="flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7]">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Project Dashboard Status</h2>
                        <p class="text-xs text-gray-500">Visualization of the status of all your projects</p>
                    </div>
                </div>
            </div>

            {{-- Content --}}
            <div class="flex flex-col items-center justify-center flex-1 p-4">

                {{-- Chart --}}
                <div class="w-64 h-64 p-4">
                    <canvas id="projectPieChart"></canvas>
                </div>

                {{-- Legend satu baris --}}
                <div class="flex flex-wrap justify-center gap-4 mt-4 text-sm text-gray-600">

                    @php
                        $chartConfig = [
                            'planning' => ['label' => 'Planning', 'color' => '#94a3b8'],
                            'active' => ['label' => 'Active', 'color' => '#10b981'],
                            'on_hold' => ['label' => 'On Hold', 'color' => '#f59e0b'],
                            'completed' => ['label' => 'Completed', 'color' => '#3b82f6'],
                            'cancelled' => ['label' => 'Cancelled', 'color' => '#ef4444'],
                        ];
                    @endphp

                    @foreach ($chartConfig as $key => $cfg)
                        @php $count = $projectStats[$key] ?? 0; @endphp

                        <div class="flex items-center gap-2">
                            <span class="w-3 h-3 rounded-sm" style="background: {{ $cfg['color'] }}"></span>

                            <span>
                                {{ $cfg['label'] }} -
                                <span class="font-semibold text-gray-800">{{ $count }}</span>
                            </span>
                        </div>
                    @endforeach

                </div>

            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

        <script>
            const ctx = document.getElementById('projectPieChart');

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled'],
                    datasets: [{
                        data: [
                            {{ $projectStats['planning'] ?? 0 }},
                            {{ $projectStats['active'] ?? 0 }},
                            {{ $projectStats['on_hold'] ?? 0 }},
                            {{ $projectStats['completed'] ?? 0 }},
                            {{ $projectStats['cancelled'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#94a3b8',
                            '#10b981',
                            '#f59e0b',
                            '#3b82f6',
                            '#ef4444'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 10
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            backgroundColor: "#111827",
                            padding: 10,
                            cornerRadius: 6,
                            titleFont: {
                                size: 13
                            },
                            bodyFont: {
                                size: 12
                            },
                            callbacks: {
                                label: function(context) {
                                    return context.label + ": " + context.raw + " Projects";
                                }
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 1000
                    }
                }
            });
        </script>


        {{-- Widget 2: Deadline Approaching --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col"
            style="height: 420px;">
            <div
                class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-amber-100 to-orange-100 flex items-center justify-center text-amber-500">
                        <i class="fa-solid fa-clock"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Deadline Approaching</h2>
                        <p class="text-xs text-gray-500">Task is due in the next 3 days</p>
                    </div>
                </div>
                @if ($deadlineTasks->count() > 0)
                    <span class="px-2.5 py-1 text-xs font-bold bg-red-100 text-red-600 rounded-full">
                        {{ $deadlineTasks->count() }}
                    </span>
                @endif
            </div>

            <div class="p-4 space-y-2 flex-1 overflow-y-auto">
                @forelse ($deadlineTasks as $task)
                    @php
                        $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($task->due_date), false);
                        $urgentColor =
                            $daysLeft < 0
                                ? 'border-red-300 bg-red-50 hover:bg-red-100'
                                : ($daysLeft <= 1
                                    ? 'border-orange-300 bg-orange-50 hover:bg-orange-100'
                                    : 'border-yellow-200 bg-yellow-50 hover:bg-yellow-100');
                        $badgeBg =
                            $daysLeft < 0
                                ? 'bg-red-500 text-white'
                                : 'bg-yellow-400 text-white';
                        $badgeText =
                            $daysLeft < 0
                                ? 'Late by ' . abs($daysLeft) . 'd'
                                : 'Almost Due';
                    @endphp
                    <div
                        onclick="window.location.href='{{ route('tasks.show', $task->id) }}'"
                        class="rounded-xl border {{ $urgentColor }} p-3 flex items-center justify-between gap-3 cursor-pointer transition-all"
                        style="user-select: none;">

                        {{-- Kiri: nama task + project + tanggal --}}
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-gray-900 truncate">{{ $task->name }}</p>
                            @if ($task->project)
                                <p class="text-xs text-gray-500 mt-0.5 truncate">
                                    <i class="fa-solid fa-folder-open mr-1"></i>{{ $task->project->name }}
                                </p>
                            @endif
                            <p class="text-xs text-gray-400 mt-1">
                                <i class="fa-regular fa-calendar mr-1"></i>
                                {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                            </p>
                        </div>

                        {{-- Kanan: badge status --}}
                        <span class="flex-shrink-0 text-[10px] font-bold px-2.5 py-1 rounded-full {{ $badgeBg }}">
                            {{ $badgeText }}
                        </span>

                    </div>
                @empty
                    <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-center">
                        <p class="text-green-600 font-medium text-sm">
                            <i class="fa-solid fa-circle-check mr-1"></i> There is no approaching deadline
                        </p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Widget 3: Recent Tasks --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col"
            style="height: 420px;">
            <div
                class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7]">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Recent Tasks</h2>
                        <p class="text-xs text-gray-500">Your latest activity</p>
                    </div>
                </div>
                <a href="{{ route('tasks.index') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                    See all <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
                </a>
            </div>
            @if ($recentTasks->isEmpty())
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div
                            class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-regular fa-clipboard text-2xl text-indigo-600"></i>
                        </div>
                        <p class="text-gray-700 font-semibold text-sm">No recent tasks found</p>
                        <a href="{{ route('tasks.create') }}"
                            class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <i class="fa-solid fa-plus"></i> Create Task
                        </a>
                    </div>
                </div>
            @else
                <div class="divide-y divide-gray-100 flex-1 overflow-y-auto">
                    @foreach ($recentTasks as $task)
                        @php
                            $config = $statusConfig[$task->status] ?? $statusConfig['to_do'];
                            $isOverdue = $task->due_date?->isPast() && $task->status !== 'completed';
                        @endphp
                        <div class="px-5 py-3 flex items-center justify-between gap-3 hover:bg-gray-50 transition-colors cursor-pointer group"
                            onclick="window.location.href='{{ route('tasks.show', $task) }}'">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                <div
                                    class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7] font-semibold text-xs flex-shrink-0">
                                    {{ strtoupper(substr($task->name, 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                        {{ $task->name }}</p>
                                    <p class="text-xs text-gray-400 truncate">{{ $task->project?->name ?? '—' }}</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-shrink-0">
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border {{ $config['class'] }}">
                                    <i class="fa-solid {{ $config['icon'] }} text-[9px]"></i>
                                    {{ $config['label'] }}
                                </span>
                                @if ($task->due_date)
                                    <span class="text-xs {{ $isOverdue ? 'text-red-500' : 'text-gray-400' }}">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Widget 4: Active Projects --}}
        <div class="bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col"
            style="height: 420px;">
            <div
                class="px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-100 to-[#5DDA52] flex items-center justify-center text-[#088B01]">
                        <i class="fa-regular fa-folder-open"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Active Projects</h2>
                        <p class="text-xs text-gray-500">Ongoing project</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('projects.index') }}"
                        class="text-xs font-medium text-indigo-600 hover:text-indigo-700">
                        See all <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
                    </a>
                </div>
            </div>
            @if ($activeProjects->isEmpty())
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div
                            class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-regular fa-folder text-2xl text-indigo-600"></i>
                        </div>
                        <p class="text-gray-700 font-semibold text-sm">No active projects</p>
                        <a href="{{ route('projects.create') }}"
                            class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <i class="fa-solid fa-plus"></i> New Project
                        </a>
                    </div>
                </div>
            @else
                <div class="divide-y divide-gray-100 flex-1 overflow-y-auto">
                    @foreach ($activeProjects as $project)
                        @php
                            $projectProgress = $project->calculateProgress();
                            $progressColor =
                                $projectProgress >= 100
                                    ? 'from-emerald-500 to-teal-500'
                                    : ($projectProgress >= 75
                                        ? 'from-indigo-500 to-violet-500'
                                        : ($projectProgress >= 50
                                            ? 'from-amber-500 to-orange-500'
                                            : 'from-red-400 to-rose-500'));
                        @endphp
                        <div class="px-5 py-3 hover:bg-gray-50 transition-all group cursor-pointer"
                            onclick="window.location.href='{{ route('projects.show', $project) }}'">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7] font-semibold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($project->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                            {{ $project->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $project->workspace?->name ?? 'No Workspace' }}</p>
                                    </div>
                                </div>
                                <span
                                    class="text-xs font-bold {{ $projectProgress >= 100 ? 'text-emerald-600' : 'text-gray-700' }} flex-shrink-0">
                                    {{ round($projectProgress) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="bg-gradient-to-r {{ $progressColor }} h-1.5 rounded-full transition-all duration-500"
                                    style="width: {{ min($projectProgress, 100) }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
    {{-- END GRID --}}

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const projectStats = @json($projectStats);
            const config = {
                planning: {
                    label: 'Planning',
                    color: '#94a3b8'
                },
                active: {
                    label: 'Active',
                    color: '#10b981'
                },
                on_hold: {
                    label: 'On Hold',
                    color: '#f59e0b'
                },
                completed: {
                    label: 'Completed',
                    color: '#3b82f6'
                },
                cancelled: {
                    label: 'Cancelled',
                    color: '#ef4444'
                },
            };
            const labels = [],
                data = [],
                colors = [];
            let total = 0;
            Object.entries(config).forEach(([key, cfg]) => {
                const val = projectStats[key] ?? 0;
                labels.push(cfg.label);
                data.push(val);
                colors.push(cfg.color);
                total += val;
            });
            document.getElementById('projectTotalLabel').textContent = total;
            new Chart(document.getElementById('projectPieChart'), {
                type: 'doughnut',
                data: {
                    labels,
                    datasets: [{
                        data,
                        backgroundColor: colors,
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 8
                    }]
                },
                options: {
                    cutout: '65%',
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: ctx =>
                                    ` ${ctx.label}: ${ctx.raw} project (${total > 0 ? Math.round(ctx.raw / total * 100) : 0}%)`
                            }
                        }
                    },
                    animation: {
                        animateRotate: true,
                        duration: 800
                    }
                }
            });
        });
    </script>

    {{-- Floating Action Ball --}}
    <div id="fab-container" class="fixed bottom-20 right-6 z-50 select-none">
        <div id="fab-menu"
            class="absolute w-max flex flex-col items-end gap-2 opacity-0 pointer-events-none transition-all duration-300 scale-90 origin-bottom-right">
            <a href="{{ route('workspaces.create') }}"
                class="fab-item bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-2xl shadow-lg flex items-center gap-2.5 text-sm font-medium hover:bg-violet-50 hover:border-violet-300 hover:text-violet-700 transition-all duration-200 whitespace-nowrap opacity-0 -translate-x-2">
                <span class="w-7 h-7 bg-violet-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-folder-tree text-violet-600 text-xs"></i>
                </span>
                New Workspace
            </a>
            <a href="{{ route('projects.create') }}"
                class="fab-item bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-2xl shadow-lg flex items-center gap-2.5 text-sm font-medium hover:bg-violet-50 hover:border-violet-300 hover:text-violet-700 transition-all duration-200 whitespace-nowrap opacity-0 -translate-x-2">
                <span class="w-7 h-7 bg-indigo-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-diagram-project text-indigo-600 text-xs"></i>
                </span>
                New Project
            </a>
            <a href="{{ route('tasks.create') }}"
                class="fab-item bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-2xl shadow-lg flex items-center gap-2.5 text-sm font-medium hover:bg-violet-50 hover:border-violet-300 hover:text-violet-700 transition-all duration-200 whitespace-nowrap opacity-0 -translate-x-2">
                <span class="w-7 h-7 bg-emerald-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-list-check text-emerald-600 text-xs"></i>
                </span>
                New Task
            </a>
        </div>
        <button id="fab-btn"
            class="w-14 h-14 rounded-full shadow-2xl flex items-center justify-center cursor-grab active:cursor-grabbing transition-all duration-200 relative overflow-hidden bg-[#0096c7] border border-white/10">
            <span class="absolute inset-0 rounded-full bg-gradient-to-br from-white/20 to-transparent"></span>
            <span id="fab-icon" class="relative z-10 text-white text-xl transition-transform duration-300">
                <i class="fa-solid fa-plus"></i>
            </span>
        </button>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fabBtn = document.getElementById('fab-btn');
            const fabMenu = document.getElementById('fab-menu');
            const fabIcon = document.getElementById('fab-icon');
            const fabContainer = document.getElementById('fab-container');
            const fabItems = document.querySelectorAll('.fab-item');
            let isOpen = false;

            function updateMenuDirection() {
                const rect = fabContainer.getBoundingClientRect();
                const nearTop = rect.top < 150;
                const nearLeft = rect.left < 150;

                fabMenu.style.top = '';
                fabMenu.style.bottom = '';
                fabMenu.style.left = '';
                fabMenu.style.right = '';
                fabMenu.style.transform = '';

                // Arah vertikal
                if (nearTop) {
                    fabMenu.style.top = '64px';
                } else {
                    fabMenu.style.bottom = '64px';
                }

                // Arah horizontal
                if (nearLeft) {
                    fabMenu.style.left = '0';
                    fabMenu.style.transformOrigin = 'bottom left';
                    fabItems.forEach(item => {
                        item.classList.remove('-translate-x-2');
                        item.classList.add('translate-x-2');
                    });
                } else {
                    fabMenu.style.right = '0';
                    fabMenu.style.transformOrigin = 'bottom right';
                    fabItems.forEach(item => {
                        item.classList.remove('translate-x-2');
                        item.classList.add('-translate-x-2');
                    });
                }
            }

            fabBtn.addEventListener('click', function(e) {
                updateMenuDirection();
                if (!fabContainer.classList.contains('dragging')) {
                    isOpen = !isOpen;
                    if (isOpen) {
                        fabMenu.classList.remove('opacity-0', 'scale-90', 'pointer-events-none');
                        fabMenu.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
                        fabIcon.classList.add('rotate-45');
                        fabItems.forEach((item, index) => {
                            setTimeout(() => {
                                item.classList.remove('opacity-0', '-translate-x-2');
                                item.classList.add('opacity-100', 'translate-x-0');
                            }, 50 * (index + 1));
                        });
                    } else {
                        fabMenu.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
                        fabMenu.classList.add('opacity-0', 'scale-90', 'pointer-events-none');
                        fabIcon.classList.remove('rotate-45');
                        fabItems.forEach(item => {
                            item.classList.remove('opacity-100', 'translate-x-0');
                            item.classList.add('opacity-0', '-translate-x-2');
                        });
                    }
                }
            });

            let isDragging = false;
            let startX, startY, initialLeft, initialTop;
            fabBtn.addEventListener('mousedown', function(e) {
                isDragging = false;
                startX = e.clientX;
                startY = e.clientY;
                const rect = fabContainer.getBoundingClientRect();
                initialLeft = rect.left;
                initialTop = rect.top;
                fabContainer.classList.add('dragging');
                fabContainer.style.transition = 'none';
            });
            document.addEventListener('mousemove', function(e) {
                if (fabContainer.classList.contains('dragging')) {

                    const dx = e.clientX - startX;
                    const dy = e.clientY - startY;

                    let newLeft = initialLeft + dx;
                    let newTop = initialTop + dy;

                    const rect = fabContainer.getBoundingClientRect();
                    const maxLeft = window.innerWidth - rect.width;
                    const maxTop = window.innerHeight - rect.height;

                    // Batasi agar tidak keluar layar
                    newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                    newTop = Math.max(0, Math.min(newTop, maxTop));

                    fabContainer.style.left = newLeft + 'px';
                    fabContainer.style.top = newTop + 'px';
                    fabContainer.style.bottom = 'auto';
                    fabContainer.style.right = 'auto';
                }
            });
            document.addEventListener('mouseup', function() {
                if (fabContainer.classList.contains('dragging')) {
                    fabContainer.classList.remove('dragging');
                    fabContainer.style.transition = '';
                }
            });

        });
    </script>
@endsection
