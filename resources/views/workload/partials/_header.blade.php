<header class="mb-5">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <div class="flex flex-wrap items-center gap-2">
                <h1 class="text-3xl font-bold text-slate-900">Workload Monitoring</h1>
                <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                    {{ $period['label'] }}
                </span>
            </div>
            <p class="mt-1 max-w-3xl text-sm text-slate-500">
                Pantau distribusi task aktif dan risiko beban tugas anggota pada project yang dapat kamu akses.
            </p>
        </div>

        <button
            type="button"
            data-workload-calculation-open
            aria-controls="workload-calculation-modal"
            class="inline-flex min-h-10 shrink-0 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-sky-300 hover:bg-sky-50 hover:text-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
        >
            <i class="fa-solid fa-circle-info text-sky-600" aria-hidden="true"></i>
            Cara Perhitungan
        </button>
    </div>

    <div
        class="mt-4 flex items-start gap-2 border-l-2 border-sky-400 bg-sky-50/60 px-3 py-2.5"
        role="note"
        data-workload-disclaimer
    >
        <i
            class="fa-solid fa-circle-info mt-0.5 shrink-0 text-sm text-sky-600"
            aria-hidden="true"
        ></i>

        <p class="text-xs leading-5 text-slate-600 sm:text-sm">
            {{ $disclaimer }}
        </p>
    </div>
</header>
