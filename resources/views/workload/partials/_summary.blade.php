<section class="mb-6" aria-labelledby="workload-summary-title">
    <div class="mb-3 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 id="workload-summary-title" class="font-semibold text-slate-900">Ringkasan periode</h2>
            <p class="mt-1 text-xs text-slate-500">
                {{ \Carbon\Carbon::parse($period['start'])->format('d M Y') }}–{{ \Carbon\Carbon::parse($period['end'])->format('d M Y') }}
            </p>
        </div>
        <p class="text-xs text-slate-500">{{ $summary['total_members'] }} anggota unik</p>
    </div>

    @php
        $summaryCards = [
            ['label' => 'Normal', 'value' => $summary['normal_count'], 'class' => 'border-emerald-200 bg-emerald-50 text-emerald-800'],
            ['label' => 'Perlu Perhatian', 'value' => $summary['attention_count'], 'class' => 'border-amber-200 bg-amber-50 text-amber-800'],
            ['label' => 'Risiko Tinggi', 'value' => $summary['high_risk_count'], 'class' => 'border-orange-200 bg-orange-50 text-orange-800'],
            ['label' => 'Kritis', 'value' => $summary['critical_count'], 'class' => 'border-red-200 bg-red-50 text-red-800'],
            ['label' => 'Overdue', 'value' => $summary['overdue_count'], 'class' => 'border-rose-200 bg-rose-50 text-rose-800'],
            ['label' => 'Unscheduled', 'value' => $summary['unscheduled_count'], 'class' => 'border-slate-200 bg-slate-100 text-slate-700'],
        ];
    @endphp

    <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
        @foreach ($summaryCards as $card)
            <article class="rounded-xl border px-4 py-3 {{ $card['class'] }}">
                <p class="text-xl font-bold">{{ $card['value'] }}</p>
                <p class="mt-0.5 text-xs font-semibold">{{ $card['label'] }}</p>
            </article>
        @endforeach
    </div>
</section>
