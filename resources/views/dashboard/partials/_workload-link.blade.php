@if ($canViewWorkload ?? false)
    <div class="mb-6 px-4 sm:px-6">
        <a href="{{ route('overload.index') }}"
            class="flex flex-col gap-3 rounded-2xl border border-sky-200 bg-gradient-to-r from-sky-50 to-cyan-50 p-5 transition hover:border-sky-300 hover:shadow-sm sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-700 text-white">
                    <i class="fa-solid fa-chart-column" aria-hidden="true"></i>
                </span>
                <div>
                    <h2 class="font-semibold text-slate-900">Workload Monitoring</h2>
                    <p class="mt-1 text-sm text-slate-600">
                        Buka laporan terpusat untuk memantau distribusi task aktif sesuai scope aksesmu.
                    </p>
                </div>
            </div>
            <span class="inline-flex items-center gap-2 text-sm font-semibold text-sky-700">
                Buka monitoring
                <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </span>
        </a>
    </div>
@endif
