@php
    $indentClass = match ($taskNode['depth']) {
        1 => 'ml-3 border-indigo-200 sm:ml-6',
        2 => 'ml-6 border-violet-200 sm:ml-12',
        default => 'border-sky-200',
    };
@endphp

<div class="{{ $indentClass }} rounded-xl border-l-4 bg-white p-4 shadow-sm">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-2">
                <i class="fa-solid {{ $taskNode['is_leaf'] ? 'fa-circle-check text-sky-500' : 'fa-folder-tree text-indigo-500' }} text-xs"></i>
                <h3 class="break-words text-sm font-semibold text-slate-900">{{ $taskNode['name'] }}</h3>
                <span class="rounded-full px-2 py-0.5 text-[11px] font-semibold {{ $taskNode['is_leaf'] ? 'bg-sky-50 text-sky-700' : 'bg-indigo-50 text-indigo-700' }}">
                    {{ $taskNode['is_leaf'] ? 'Leaf' : 'Parent' }}
                </span>
            </div>

            @if ($taskNode['description'])
                <p class="mt-1 text-xs leading-5 text-slate-500">{{ $taskNode['description'] }}</p>
            @endif

            <p class="mt-2 text-xs font-medium text-slate-600">
                @if ($taskNode['is_leaf'])
                    Weight {{ number_format($taskNode['weight'], 2) }}
                @else
                    Beban turunan {{ number_format($taskNode['aggregate_weight'], 2) }}
                @endif
                · Hari ke-{{ $taskNode['start_offset_days'] + 1 }}
                · Durasi {{ $taskNode['duration_days'] }} hari
            </p>

            @if ($taskNode['predecessor'])
                <p class="mt-2 text-xs font-medium text-indigo-600">
                    <i class="fa-solid fa-link mr-1"></i>
                    {{ $taskNode['predecessor']['dependency_type'] }} dari
                    {{ $taskNode['predecessor']['name'] }} · Lag
                    {{ $taskNode['predecessor']['lag_days'] }} hari
                </p>
            @endif
        </div>
    </div>
</div>

@foreach ($taskNode['children'] as $childNode)
    @include('project-template-gallery.partials._template-tree', ['taskNode' => $childNode])
@endforeach
