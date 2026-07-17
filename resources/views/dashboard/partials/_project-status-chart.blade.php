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

<div
    class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-visible flex flex-col cursor-grab active:cursor-grabbing"
    style="height:420px;"
    data-id="widget-chart">
    <div
        class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent">
        <div class="flex items-center gap-3">
            <div
                class="w-8 h-8 rounded-xl bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7]">
                <i class="fa-solid fa-chart-pie"></i>
            </div>

            <div>
                <h2 class="text-sm font-bold text-gray-900">
                    Project Dashboard Status
                </h2>

                <p class="text-xs text-gray-500">
                    Visualization of the status of all your projects
                </p>
            </div>
        </div>
    </div>

    <div class="flex flex-col items-center justify-center flex-1 p-4">
        <div class="w-64 h-64 p-4">
            <canvas id="projectPieChart"></canvas>
        </div>

        <div class="flex flex-wrap justify-center gap-4 mt-4 text-sm text-gray-600">
            @foreach ($chartConfig as $key => $cfg)
                @php
                    $count = $projectStats[$key] ?? 0;
                @endphp

                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-sm" style="background: {{ $cfg['color'] }}"></span>

                    <span>
                        {{ $cfg['label'] }} -
                        <span class="font-semibold text-gray-800">
                            {{ $count }}
                        </span>
                    </span>
                </div>
            @endforeach
        </div>
    </div>
</div>
