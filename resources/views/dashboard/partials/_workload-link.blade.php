@if ($workloadSummary !== null)
    @php
        $presentation = match ($workloadSummary['highest_level']) {
            'critical' => [
                'title' => 'Workload Kritis',
                'description' => "{$workloadSummary['critical_count']} anggota membutuhkan evaluasi pembagian task segera.",
                'container' => 'border-red-200 bg-red-50/70',
                'icon_container' => 'bg-red-100 text-red-700',
                'icon' => 'fa-circle-exclamation',
                'cta' => 'text-red-700 hover:text-red-900',
            ],
            'high_risk' => [
                'title' => 'Risiko Beban Tugas Tinggi',
                'description' => "{$workloadSummary['high_risk_count']} anggota berada pada level Risiko Tinggi.",
                'container' => 'border-orange-200 bg-orange-50/70',
                'icon_container' => 'bg-orange-100 text-orange-700',
                'icon' => 'fa-fire-flame-curved',
                'cta' => 'text-orange-700 hover:text-orange-900',
            ],
            'attention' => [
                'title' => 'Workload Perlu Perhatian',
                'description' => "{$workloadSummary['attention_count']} anggota memiliki distribusi task yang perlu dipantau.",
                'container' => 'border-amber-200 bg-amber-50/70',
                'icon_container' => 'bg-amber-100 text-amber-700',
                'icon' => 'fa-triangle-exclamation',
                'cta' => 'text-amber-700 hover:text-amber-900',
            ],
            default => [
                'title' => 'Workload Monitoring',
                'description' => "Tidak ada risiko beban tugas pada periode {$workloadSummary['period_label']}.",
                'container' => 'border-sky-200 bg-sky-50/70',
                'icon_container' => 'bg-sky-100 text-sky-700',
                'icon' => 'fa-circle-check',
                'cta' => 'text-sky-700 hover:text-sky-900',
            ],
        };
    @endphp

    <div class="mb-6 px-4 sm:px-6">
        <div class="flex flex-col gap-4 rounded-xl border p-4 sm:flex-row sm:items-center sm:justify-between {{ $presentation['container'] }}"
            data-dashboard-workload data-workload-level="{{ $workloadSummary['highest_level'] }}">
            <div class="flex min-w-0 items-start gap-3">
                <span
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $presentation['icon_container'] }}">
                    <i class="fa-solid {{ $presentation['icon'] }}" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h2 class="font-semibold text-slate-900">{{ $presentation['title'] }}</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        {{ $presentation['description'] }}
                    </p>
                    @if ($workloadSummary['affected_count'] > 0)
                        <p class="mt-1 text-xs font-medium text-slate-600">
                            {{ $workloadSummary['critical_count'] }} Kritis ·
                            {{ $workloadSummary['high_risk_count'] }} Risiko Tinggi ·
                            {{ $workloadSummary['attention_count'] }} Perlu Perhatian
                        </p>
                    @endif
                    <p class="mt-1 text-xs text-slate-500">
                        Berdasarkan Skor Beban Tugas untuk 7 Hari ke Depan.
                    </p>
                </div>
            </div>

            <a href="{{ $workloadSummary['report_url'] }}"
                class="inline-flex shrink-0 items-center justify-center gap-2 rounded-lg border border-current/20 bg-white/70 px-3.5 py-2 text-sm font-semibold transition {{ $presentation['cta'] }}">
                Buka monitoring
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </a>
        </div>
    </div>
@endif
