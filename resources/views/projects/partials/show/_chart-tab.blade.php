<div
    id="project-tab-chart"
    class="project-tab-content relative
        {{ $currentTab !== 'chart' ? 'hidden' : '' }}"
>
    <div class="p-6">
        <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-lg font-bold text-gray-900">
                S-Curve: Planned vs Actual
            </h3>

            <span class="flex items-center gap-2 text-sm text-gray-500">
                <i class="fa-regular fa-calendar"></i>

                Baseline:
                {{ $baseline?->baseline_name ?? 'No active baseline' }}
            </span>
        </div>

        @if (empty($chartData['labels']))
            <div class="rounded-xl border border-gray-200 bg-gray-50 p-16 text-center">
                <i class="fa-solid fa-chart-line text-5xl text-gray-300"></i>

                <p class="mt-4 font-medium text-gray-600">
                    No progress data available yet.
                </p>

                <p class="mt-1 text-sm text-gray-500">
                    Start adding tasks and updating progress to display the chart.
                </p>
            </div>
        @else
            <div class="h-96 rounded-xl border border-gray-200 bg-gray-50 p-4">
                <canvas id="projectProgressChart"></canvas>
            </div>
        @endif
    </div>
</div>
