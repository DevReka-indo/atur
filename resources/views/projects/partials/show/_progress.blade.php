@php
    $progressValue = min(max(round($progress, 1), 0), 100);
    $hue = ($progressValue / 100) * 120;
    $colorStart = "hsl({$hue}, 65%, 75%)";
    $colorEnd = 'hsl(' . ($hue + 10) . ', 70%, 70%)';

    $progressTextClass = $progressValue >= 100
        ? 'text-emerald-500'
        : 'text-gray-600';
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm">
    <div class="mb-3 flex items-center justify-between">
        <span class="text-sm font-medium text-gray-700">
            Overall Progress
        </span>

        <span class="text-2xl font-bold {{ $progressTextClass }}">
            {{ number_format($progressValue, 1) }}%
        </span>
    </div>

    <div class="h-3 w-full overflow-hidden rounded-full bg-gray-100">
        <div
            class="h-3 rounded-full transition-all duration-500"
            style="
                width: {{ $progressValue }}%;
                background: linear-gradient(
                    90deg,
                    {{ $colorStart }},
                    {{ $colorEnd }}
                );
                box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
            "
        ></div>
    </div>
</div>
