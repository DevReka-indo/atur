<dl class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
    @foreach ([
        ['label' => 'Seluruh Task', 'value' => $summary['tasks_count']],
        ['label' => 'Root Task', 'value' => $summary['root_tasks_count']],
        ['label' => 'Leaf Task', 'value' => $summary['leaf_tasks_count']],
        ['label' => 'Level Hierarchy', 'value' => $summary['hierarchy_levels']],
        ['label' => 'Total Beban', 'value' => number_format($summary['total_leaf_weight'], 2)],
        ['label' => 'Durasi Kalender', 'value' => $summary['duration_days'].' hari'],
    ] as $stat)
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <dt class="text-xs font-medium text-slate-500">{{ $stat['label'] }}</dt>
            <dd class="mt-1 text-xl font-bold text-slate-900">{{ $stat['value'] }}</dd>
        </div>
    @endforeach
</dl>
