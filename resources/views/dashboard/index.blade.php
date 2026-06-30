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
            'urgent' => [
                'class' => 'bg-red-100 text-red-400 border-red-200 line-through',
                'icon' => 'fa-circle-xmark',
                'label' => 'Urgent',
            ],
        ];
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    {{-- Header --}}
    <div class="px-4 sm:px-6 lg:px-8 py-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                    Welcome, {{ Auth::user()->name }}
                </h1>
                <p class="mt-2 text-sm sm:text-base text-gray-500 max-w-2xl">
                    Track your project progress, upcoming deadlines, and team activity at a glance.
                </p>
            </div>

            {{-- Date --}}
            <div class="flex-shrink-0">
                <span
                    class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg shadow-sm text-sm font-medium text-gray-600">
                    <i class="fa-regular fa-calendar text-gray-400"></i>
                    {{ now()->format('l, d F Y') }}
                </span>
            </div>
        </div>
    </div>

    {{--  Cards --}}
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

        <a href="{{ route('projects.index', ['status' => 'completed']) }}"
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

    {{-- URGENT ALERT --}}
    @if (($projectStats['urgent'] ?? 0) > 0)
        <div id="urgent-projects-alert" class="mb-6 px-4 sm:px-6">
            <div style="background: linear-gradient(to right, #fee2e2, #fce7f3); border-radius: 12px; padding: 16px 20px; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.1); cursor: pointer; transition: all 0.3s ease;"
                onmouseover="this.style.boxShadow='0 10px 20px rgba(220, 38, 38, 0.2)'; this.style.transform='translateY(-2px)'"
                onmouseout="this.style.boxShadow='0 4px 6px rgba(220, 38, 38, 0.1)'; this.style.transform='translateY(0)'"
                onclick="window.location.href='{{ route('projects.index', ['status' => 'urgent']) }}'">
                <div class="flex items-center justify-between gap-4">

                    {{-- Left Content --}}
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            style="width: 48px; height: 48px; background: linear-gradient(135deg, #dc2626, #ef4444); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; box-shadow: 0 4px 6px rgba(220, 38, 38, 0.3);">
                            <i class="fa-solid fa-triangle-exclamation"
                                style="color: white; font-size: 24px; animation: pulse 2s infinite;"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p
                                style="font-size: 15px; font-weight: 700; color: #991b1b; display: flex; align-items: center; gap: 8px; margin: 0;">
                                <i class="fa-solid fa-circle-exclamation" style="color: #dc2626;"></i>
                                Urgent Projects Require Attention
                            </p>
                            <p style="font-size: 13px; color: #dc2626; margin-top: 4px; font-weight: 500;">
                                You have <span
                                    style="font-weight: 700; color: #991b1b; font-size: 16px;">{{ $projectStats['urgent'] }}</span>
                                urgent project{{ $projectStats['urgent'] > 1 ? 's' : '' }}
                            </p>
                        </div>
                    </div>

                    {{-- Right Content --}}
                    <div class="flex items-center gap-3 flex-shrink-0">
                        <span
                            style="display: none; sm:inline-flex; align-items: center; gap: 6px; padding: 6px 12px; background: #dc2626; color: white; font-size: 12px; font-weight: 700; border-radius: 8px;">
                            View Now
                            <i class="fa-solid fa-arrow-right" style="font-size: 10px;"></i>
                        </span>
                        <button onclick="event.stopPropagation(); closeUrgentAlert()"
                            style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center; border-radius: 8px; background: #fee2e2; color: #dc2626; border: none; cursor: pointer; font-size: 18px; font-weight: bold; transition: all 0.2s;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- OVERLOAD ALERT --}}
    @if ($overloadedMembers->count() > 0)
        <div id="overload-alert" class="mb-6 px-4 sm:px-6">
            <div style="background: linear-gradient(to right, #fff7ed, #fef3c7); border-radius: 12px; padding: 16px 20px; box-shadow: 0 4px 6px rgba(234, 88, 12, 0.1); cursor: pointer; transition: all 0.3s ease;"
                onmouseover="this.style.boxShadow='0 10px 20px rgba(234,88,12,0.2)'; this.style.transform='translateY(-2px)'"
                onmouseout="this.style.boxShadow='0 4px 6px rgba(234,88,12,0.1)'; this.style.transform='translateY(0)'"
                onclick="window.location.href='{{ route('overload.index') }}'">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div
                            style="width:48px; height:48px; background: linear-gradient(135deg, #ea580c, #f97316); border-radius:12px; display:flex; align-items:center; justify-content:center; flex-shrink:0; box-shadow: 0 4px 6px rgba(234,88,12,0.3);">
                            <i class="fa-solid fa-user-clock" style="color:white; font-size:22px;"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p style="font-size:15px; font-weight:700; color:#9a3412; margin:0;">
                                <i class="fa-solid fa-triangle-exclamation" style="color:#ea580c;"></i>
                                {{ $overloadedMembers->count() }} Member Overload Terdeteksi
                            </p>
                            <p style="font-size:13px; color:#c2410c; margin-top:4px;">
                                @foreach ($overloadedMembers->take(3) as $om)
                                    <span style="font-weight:600;">{{ $om['name'] }}</span>
                                    ({{ $om['project'] }} · {{ $om['task_count'] }} tasks)
                                    {{ !$loop->last ? ', ' : '' }}
                                @endforeach
                                @if ($overloadedMembers->count() > 3)
                                    <span style="font-weight:600;"> +{{ $overloadedMembers->count() - 3 }} lainnya</span>
                                @endif
                            </p>
                        </div>
                    </div>
                    <button onclick="event.stopPropagation(); closeOverloadAlert()"
                        style="width:32px; height:32px; display:flex; align-items:center; justify-content:center; border-radius:8px; background:#fff7ed; color:#ea580c; border:none; cursor:pointer; font-size:18px; flex-shrink:0;">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    @endif


    {{-- GRID WIDGETS --}}
    <div id="dashboard-grid" class="px-4 sm:px-6 grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">

        {{-- Pie Chart --}}
        <div class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-visible flex flex-col cursor-grab active:cursor-grabbing"
            style="height:420px;" data-id="widget-chart">

            {{-- Header --}}
            <div
                class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent">
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
                            'urgent' => ['label' => 'Urgent', 'color' => '#D50000'],
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

            const statusMapping = [
                'planning',
                'active',
                'on_hold',
                'completed',
                'cancelled',
                'urgent'
            ];

            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Planning', 'Active', 'On Hold', 'Completed', 'Cancelled', 'Urgent'],
                    datasets: [{
                        data: [
                            {{ $projectStats['planning'] ?? 0 }},
                            {{ $projectStats['active'] ?? 0 }},
                            {{ $projectStats['on_hold'] ?? 0 }},
                            {{ $projectStats['completed'] ?? 0 }},
                            {{ $projectStats['cancelled'] ?? 0 }},
                            {{ $projectStats['urgent'] ?? 0 }}
                        ],
                        backgroundColor: [
                            '#94a3b8',
                            '#10b981',
                            '#f59e0b',
                            '#3b82f6',
                            '#ef4444',
                            '#D50000'
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
                    },

                    onClick: (e, activeElements) => {
                        if (activeElements.length > 0) {
                            const element = activeElements[0];
                            const index = element.index;
                            const status = statusMapping[index];
                            if (status) {
                                window.location.href = "{{ route('projects.index') }}?status=" + status;
                            }
                        }
                    },

                    onHover: (e, activeElements) => {
                        const canvas = e.chart.canvas;
                        canvas.style.cursor = (activeElements.length > 0) ? 'pointer' : 'default';
                    }
                }
            });
        </script>


        {{-- Deadline Approaching --}}
        <div class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col cursor-grab active:cursor-grabbing"
            style="height: 420px;" data-id="widget-deadline">
            <div
                class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
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

            <div class="no-drag p-4 space-y-2 flex-1 overflow-y-auto">
                @forelse ($deadlineTasks as $task)
                    @php
                        $daysLeft = (int) now()->diffInDays(\Carbon\Carbon::parse($task->due_date), false);
                        $urgentColor =
                            $daysLeft < 0
                                ? 'border-red-300 bg-red-50 hover:bg-red-100'
                                : ($daysLeft <= 1
                                    ? 'border-orange-300 bg-orange-50 hover:bg-orange-100'
                                    : 'border-yellow-200 bg-yellow-50 hover:bg-yellow-100');
                        $badgeBg = $daysLeft < 0 ? 'bg-red-500 text-white' : 'bg-yellow-400 text-white';
                        $badgeText = $daysLeft < 0 ? 'Late by ' . abs($daysLeft) . 'd' : 'Almost Due';
                    @endphp
                    <div onclick="window.location.href='{{ route('tasks.show', $task->token) }}'"
                        class="rounded-xl border {{ $urgentColor }} p-3 flex items-center justify-between gap-3 cursor-pointer transition-all"
                        style="user-select: none;">

                        {{-- Date --}}
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

        {{-- Recent Tasks --}}
        <div class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col cursor-grab active:cursor-grabbing"
            style="height: 420px;" data-id="widget-recent">
            <div
                class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
                <div class="flex items-center gap-3">
                    <div
                        class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7]">
                        <i class="fa-regular fa-clock"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-bold text-gray-900">Recent Tasks</h2>
                        <p class="text-xs text-gray-500">Team latest activity</p>
                    </div>
                </div>
                <a href="{{ route('tasks.index') }}"
                    class="no-drag text-xs font-medium text-indigo-600 hover:text-indigo-700">
                    See all <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
                </a>
            </div>

            @php
                $recentActivities = $recentTasks->sortByDesc('updated_at')->take(10);
            @endphp

            @if ($recentActivities->isEmpty())
                <div class="flex-1 flex items-center justify-center">
                    <div class="text-center">
                        <div
                            class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center mx-auto mb-3">
                            <i class="fa-regular fa-clipboard text-2xl text-indigo-600"></i>
                        </div>
                        <p class="text-gray-700 font-semibold text-sm">No recent activity</p>
                        <p class="text-xs text-gray-400 mt-1">Your recent tasks will appear here.</p>
                        <a href="{{ route('tasks.create') }}"
                            class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            <i class="fa-solid fa-plus"></i> Create Task
                        </a>
                    </div>
                </div>
            @else
                <div class="no-drag divide-y divide-gray-100 flex-1 overflow-y-auto">
                    @foreach ($recentActivities as $task)
                        @php
                            $config = $statusConfig[$task->status] ?? $statusConfig['to_do'];
                            $isOverdue = $task->due_date?->isPast() && $task->status !== 'completed';
                            $isUrgent = $task->priority === 'urgent' && $task->status !== 'completed';
                            $isCompleted = $task->status === 'completed';
                        @endphp
                        <div class="px-5 py-3 flex items-center justify-between gap-3
                            {{ $isUrgent ? 'bg-red-50/60 border-l-2 border-red-400' : 'hover:bg-gray-50' }}
                            {{ $isCompleted ? 'opacity-75' : '' }}
                            transition-colors cursor-pointer group"
                            onclick="window.location.href='{{ route('tasks.show', $task->token) }}'">
                            <div class="flex items-center gap-3 flex-1 min-w-0">
                                {{-- Avatar --}}
                                <div
                                    class="w-8 h-8 rounded-lg {{ $isCompleted ? 'bg-gradient-to-br from-emerald-100 to-green-100 text-emerald-600' : ($isUrgent ? 'bg-gradient-to-br from-red-100 to-orange-100 text-red-600' : 'bg-gradient-to-br from-indigo-100 to-[#A3E1EE] text-[#0096c7]') }} font-semibold text-xs flex-shrink-0 flex items-center justify-center">
                                    @if ($isCompleted)
                                        <i class="fa-solid fa-check text-xs"></i>
                                    @else
                                        {{ strtoupper(substr($task->name, 0, 1)) }}
                                    @endif
                                </div>

                                <div class="min-w-0">
                                    <p
                                        class="text-sm font-medium {{ $isCompleted ? 'line-through text-gray-400' : ($isUrgent ? 'text-red-700' : 'text-gray-900 group-hover:text-indigo-600') }} transition-colors truncate">
                                        {{ $task->name }}
                                        @if ($isUrgent)
                                            <span
                                                class="inline-flex items-center ml-1 px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-200 text-red-700">
                                                URGENT
                                            </span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-gray-400 truncate">{{ $task->project?->name ?? '—' }}</p>
                                    {{-- Timestamp --}}
                                    <p class="text-[10px] text-gray-400 mt-0.5">
                                        Updated {{ $task->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>

                            <div class="flex items-center gap-2 flex-shrink-0">
                                {{-- Status Badge --}}
                                <span
                                    class="inline-flex items-center gap-1 px-2 py-1 rounded-full text-xs font-medium border {{ $config['class'] }}">
                                    <i class="fa-solid {{ $config['icon'] }} text-[9px]"></i>
                                    {{ $config['label'] }}
                                </span>

                                {{-- Due Date --}}
                                @if ($task->due_date)
                                    <span
                                        class="text-xs {{ $isOverdue ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Active Projects --}}
        <div class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col cursor-grab active:cursor-grabbing"
            style="height: 420px;" data-id="widget-projects">
            <div
                class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
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
                        class=" no-drag text-xs font-medium text-indigo-600 hover:text-indigo-700">
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
                <div class="no-drag divide-y divide-gray-100 flex-1 overflow-y-auto">
                    @foreach ($activeProjects as $project)
                        @php
                            $projectProgress = min(round($project->calculateProgress()), 100);

                            $hue = ($projectProgress / 100) * 120;
                            $colorStart = "hsl($hue, 85%, 55%)";
                            $colorEnd = 'hsl(' . ($hue + 15) . ', 80%, 50%)';

                            $textColor = $projectProgress >= 100 ? 'text-emerald-600' : 'text-gray-700';
                        @endphp
                        <div class="px-5 py-3 hover:bg-gray-50 transition-all group cursor-pointer"
                            onclick="window.location.href='{{ route('projects.show', $project->token) }}'">
                            <div class="flex items-center justify-between gap-3 mb-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <div
                                        class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7] font-semibold text-xs flex-shrink-0">
                                        {{ strtoupper(substr($project->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p
                                            class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                            {{ $project->name }}
                                        </p>
                                        <p class="text-xs text-gray-400 truncate">
                                            {{ $project->workspace?->name ?? 'No Workspace' }}
                                        </p>
                                    </div>
                                </div>
                                <span class="text-xs font-bold {{ $textColor }} flex-shrink-0">
                                    {{ $projectProgress }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full transition-all duration-500"
                                    style="width: {{ $projectProgress }}%; background: linear-gradient(90deg, {{ $colorStart }}, {{ $colorEnd }});">
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>

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

                if (nearTop) {
                    fabMenu.style.top = '64px';
                } else {
                    fabMenu.style.bottom = '64px';
                }

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

    {{-- SortableJS CDN --}}
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const grid = document.getElementById('dashboard-grid');

            // Restore urutan tersimpan
            const saved = localStorage.getItem('dashboard-widget-order');
            if (saved) {
                try {
                    const order = JSON.parse(saved);
                    order.forEach(id => {
                        const el = grid.querySelector(`[data-id="${id}"]`);
                        if (el) grid.appendChild(el);
                    });
                } catch (e) {}
            }

            Sortable.create(grid, {
                animation: 200,
                ghostClass: 'sortable-ghost',
                chosenClass: 'sortable-chosen',
                dragClass: 'sortable-drag',
                handle: '.widget-header, .widget-card',
                filter: '.no-drag, a, button, input, select, textarea, canvas, [onclick]',
                preventOnFilter: true,

                onMove: function(evt) {
                    const target = evt.related;
                    if (target.closest('a, button, input, select, textarea, canvas, [onclick]')) {
                        return false;
                    }
                },

                onEnd: function() {
                    const order = [...grid.querySelectorAll('.widget-card')]
                        .map(el => el.dataset.id);
                    localStorage.setItem('dashboard-widget-order', JSON.stringify(order));
                }
            });
        });
    </script>

    {{-- URGENT ALERT SCRIPT --}}
    <script>
        (function() {
            const currentCount = {{ $projectStats['urgent'] ?? 0 }};
            const dismissedData = localStorage.getItem('urgent-alert-dismissed');

            if (dismissedData) {
                const parsed = JSON.parse(dismissedData);
                if (parsed.count === currentCount) {
                    const alertEl = document.getElementById('urgent-projects-alert');
                    if (alertEl) {
                        alertEl.style.display = 'none';
                    }
                } else {
                    localStorage.removeItem('urgent-alert-dismissed');
                }
            }
        })();

        function closeUrgentAlert() {
            const alert = document.getElementById('urgent-projects-alert');
            const currentCount = {{ $projectStats['urgent'] ?? 0 }};

            if (alert) {
                localStorage.setItem('urgent-alert-dismissed', JSON.stringify({
                    count: currentCount,
                    dismissedAt: new Date().toISOString()
                }));

                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.maxHeight = '0';
                alert.style.overflow = 'hidden';
                alert.style.padding = '0';
                alert.style.margin = '0';

                setTimeout(() => {
                    alert.style.display = 'none';
                }, 300);
            }
        }
    </script>


    <style>
        .sortable-ghost {
            opacity: 0.4;
            background: #e0e7ff;
            border: 2px dashed #6366f1 !important;
            border-radius: 0.75rem;
        }

        .sortable-chosen {
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15) !important;
            transform: rotate(1deg) scale(1.02);
            transition: transform 0.15s ease;
            z-index: 50;
            position: relative;
        }

        .sortable-drag {
            opacity: 1 !important;
        }
    </style>


    {{-- OVERLOAD ALERT SCRIPT --}}
    <script>
        (function() {
            const currentCount = {{ $overloadedMembers->count() }};
            const dismissedData = localStorage.getItem('overload-alert-dismissed');

            if (dismissedData) {
                const parsed = JSON.parse(dismissedData);
                if (parsed.count === currentCount) {
                    const alertEl = document.getElementById('overload-alert');
                    if (alertEl) alertEl.style.display = 'none';
                } else {
                    localStorage.removeItem('overload-alert-dismissed');
                }
            }
        })();

        function closeOverloadAlert() {
            const alert = document.getElementById('overload-alert');
            const currentCount = {{ $overloadedMembers->count() }};

            if (alert) {
                localStorage.setItem('overload-alert-dismissed', JSON.stringify({
                    count: currentCount,
                    dismissedAt: new Date().toISOString()
                }));

                alert.style.transition = 'all 0.3s ease';
                alert.style.opacity = '0';
                alert.style.maxHeight = '0';
                alert.style.overflow = 'hidden';
                alert.style.padding = '0';
                alert.style.margin = '0';

                setTimeout(() => alert.style.display = 'none', 300);
            }
        }
    </script>
@endsection
