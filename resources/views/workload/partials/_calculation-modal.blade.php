<div id="workload-calculation-modal"
    class="fixed inset-0 z-[80] hidden items-end justify-center bg-slate-950/60 p-0 sm:items-center sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="workload-calculation-title" data-workload-modal>
    <div class="max-h-[92vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-xl sm:max-w-3xl sm:rounded-2xl"
        data-workload-modal-panel tabindex="-1">
        <div class="sticky top-0 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:px-6">
            <div>
                <h2 id="workload-calculation-title" class="text-lg font-bold text-slate-900">Cara Perhitungan</h2>
                <p class="mt-1 text-xs text-slate-500">Workload Monitoring ATUR V1</p>
            </div>
            <button type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500"
                aria-label="Tutup cara perhitungan" data-workload-modal-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="flex flex-col gap-5 px-5 py-6 text-sm text-slate-600 sm:px-6">
            <p class="rounded-xl border border-sky-200 bg-sky-50 p-4 leading-6 text-sky-900">{{ $disclaimer }}</p>

            <section>
                <h3 class="font-semibold text-slate-900">Rumus dasar</h3>
                <p class="mt-2 leading-6">
                    Setiap leaf task aktif yang overlap dengan periode memberi kontribusi
                    <strong>1 ÷ jumlah assignee aktif</strong>. Skor anggota adalah jumlah seluruh kontribusi tersebut.
                </p>
            </section>

            <div class="grid gap-4 sm:grid-cols-2">
                <section class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Status yang dihitung</h3>
                    <p class="mt-2">to_do, in_progress, dan review pada project active atau urgent.</p>
                </section>
                <section class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Status yang dikecualikan</h3>
                    <p class="mt-2">completed, cancelled, stopped, status tidak dikenal, serta project non-operasional.</p>
                </section>
                <section class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Periode dan hierarchy</h3>
                    <p class="mt-2">Tanggal overlap bersifat inklusif. Hanya leaf task dihitung; parent container tidak menambah skor.</p>
                </section>
                <section class="rounded-xl border border-slate-200 p-4">
                    <h3 class="font-semibold text-slate-900">Overdue & unscheduled</h3>
                    <p class="mt-2">Overdue dicatat terpisah tanpa bonus skor. Task tanpa start/due date masuk unscheduled dan tidak menambah skor.</p>
                </section>
            </div>

            <section>
                <h3 class="font-semibold text-slate-900">Threshold level</h3>
                <ul class="mt-3 grid gap-2 sm:grid-cols-2">
                    <li class="rounded-xl bg-emerald-50 px-3 py-2 text-emerald-800">Normal: &lt; {{ $thresholds['attention'] }}</li>
                    <li class="rounded-xl bg-amber-50 px-3 py-2 text-amber-800">Perlu Perhatian: {{ $thresholds['attention'] }}–&lt; {{ $thresholds['high_risk'] }}</li>
                    <li class="rounded-xl bg-orange-50 px-3 py-2 text-orange-800">Risiko Tinggi: {{ $thresholds['high_risk'] }}–&lt; {{ $thresholds['critical'] }}</li>
                    <li class="rounded-xl bg-red-50 px-3 py-2 text-red-800">Kritis: ≥ {{ $thresholds['critical'] }}</li>
                </ul>
            </section>

            <p class="text-xs leading-5 text-slate-500">
                Notification state transition untuk Workload Monitoring akan diimplementasikan pada tahap terpisah.
            </p>
        </div>
    </div>
</div>
