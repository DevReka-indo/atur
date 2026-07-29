<div id="workload-member-detail-modal"
    class="fixed inset-0 z-[80] hidden items-end justify-center bg-slate-950/60 p-0 sm:items-center sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="workload-member-detail-title" data-workload-modal
    data-workload-detail-modal>
    <div class="max-h-[94vh] w-full overflow-y-auto rounded-t-2xl bg-white shadow-xl sm:max-w-4xl sm:rounded-2xl"
        data-workload-modal-panel tabindex="-1">
        <div class="sticky top-0 z-10 flex items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:px-6">
            <div>
                <h2 id="workload-member-detail-title" class="text-lg font-bold text-slate-900">Rincian Skor Beban Tugas</h2>
                <p class="mt-1 text-xs text-slate-500" data-workload-detail-period></p>
            </div>
            <button type="button"
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-sky-500"
                aria-label="Tutup rincian perhitungan" data-workload-modal-close>
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
            </button>
        </div>

        <div class="px-5 py-6 sm:px-6">
            <div class="rounded-xl bg-slate-50 p-5" data-workload-detail-member></div>
            <div class="mt-5 hidden rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"
                role="alert" data-workload-detail-error></div>
            <div class="mt-5 flex items-center justify-center gap-3 py-12 text-sm text-slate-500"
                data-workload-detail-loading>
                <i class="fa-solid fa-spinner fa-spin text-sky-600" aria-hidden="true"></i>
                Memuat rincian…
            </div>
            <div class="mt-6 hidden flex-col gap-6" data-workload-detail-content>
                <section>
                    <h3 class="font-semibold text-slate-900">Breakdown project</h3>
                    <div class="mt-3 grid gap-3 md:grid-cols-2" data-workload-projects></div>
                </section>
                <section>
                    <h3 class="font-semibold text-slate-900">Contributing task</h3>
                    <div class="mt-3 flex flex-col gap-3" data-workload-tasks></div>
                </section>
                <section>
                    <h3 class="font-semibold text-slate-900">Unscheduled</h3>
                    <div class="mt-3 flex flex-col gap-3" data-workload-unscheduled></div>
                </section>
                <p class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs leading-5 text-slate-500"
                    data-workload-detail-disclaimer></p>
            </div>
        </div>
    </div>
</div>
