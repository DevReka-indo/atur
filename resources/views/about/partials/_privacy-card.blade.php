<section class="h-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7" aria-labelledby="privacy-summary-title">
    <div class="flex flex-col gap-5 sm:flex-row sm:items-start">
        <span class="flex h-12 w-12 flex-none items-center justify-center rounded-xl bg-blue-50 text-blue-700">
            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Privasi &amp; Legal</p>
            <h2 id="privacy-summary-title" class="mt-2 text-xl font-bold tracking-tight text-slate-950">Kebijakan Privasi</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Pelajari jenis data yang diproses ATUR, tujuan penggunaannya, perlindungan yang diterapkan, serta hak pengguna atas data pribadi.
            </p>

            <dl class="mt-4 flex flex-wrap gap-x-6 gap-y-3 text-sm">
                <div>
                    <dt class="text-xs text-slate-500">Versi</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ config('atur.privacy_version') }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-slate-500">Berlaku sejak</dt>
                    <dd class="mt-0.5 font-semibold text-slate-900">{{ config('atur.privacy_effective_date') }}</dd>
                </div>
            </dl>

            <div class="mt-5 flex flex-col gap-2 sm:flex-row">
                <button
                    type="button"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    data-privacy-modal-open
                    aria-haspopup="dialog"
                    aria-controls="privacy-policy-modal"
                >
                    <i class="fa-solid fa-file-shield" aria-hidden="true"></i>
                    Lihat Kebijakan Privasi
                </button>
                <a
                    href="{{ route('privacy-policy.show') }}"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                >
                    Buka Halaman Penuh
                    <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </div>
</section>
