<div
    id="privacy-policy-modal"
    class="fixed inset-0 z-[70] hidden p-3 sm:p-6"
    data-privacy-modal
    aria-hidden="true"
>
    <button
        type="button"
        class="absolute inset-0 cursor-default bg-slate-950/60 backdrop-blur-sm"
        aria-label="Tutup Kebijakan Privasi"
        tabindex="-1"
        data-privacy-modal-overlay
    ></button>

    <div
        class="relative mx-auto flex h-[calc(100vh-1.5rem)] w-full max-w-5xl flex-col overflow-hidden rounded-2xl bg-white shadow-2xl sm:h-[min(90vh,900px)]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="privacy-modal-title"
        aria-describedby="privacy-modal-description"
        tabindex="-1"
        data-privacy-modal-dialog
    >
        <header class="flex flex-none items-start justify-between gap-4 border-b border-slate-200 bg-white px-5 py-4 sm:px-6">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Dokumen Legal</p>
                <h2 id="privacy-modal-title" class="mt-1 text-xl font-bold tracking-tight text-slate-950 sm:text-2xl">
                    Kebijakan Privasi
                </h2>
                <p id="privacy-modal-description" class="mt-1 text-xs text-slate-500">
                    Versi {{ config('atur.privacy_version') }} · Berlaku sejak {{ config('atur.privacy_effective_date') }}
                </p>
            </div>
            <button
                type="button"
                class="flex h-11 w-11 flex-none items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="Tutup Kebijakan Privasi"
                data-privacy-modal-close
            >
                <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
        </header>

        <div class="border-b border-slate-200 bg-slate-50 px-5 py-3 lg:hidden">
            <label for="privacy-mobile-jump" class="sr-only">Pilih bagian Kebijakan Privasi</label>
            <select
                id="privacy-mobile-jump"
                class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-700 focus:border-blue-500 focus:ring-blue-500"
                data-privacy-section-select
            >
                <option value="">Pilih bagian dokumen</option>
                <option value="privacy-general">1. Ketentuan Umum &amp; Ruang Lingkup</option>
                <option value="privacy-data">2. Data Pribadi yang Dikumpulkan</option>
                <option value="privacy-purpose">3. Tujuan Penggunaan Data</option>
                <option value="privacy-security">4. Keamanan &amp; Perlindungan Data</option>
                <option value="privacy-rights">5. Hak-Hak Pengguna</option>
                <option value="privacy-retention">6. Retensi Data &amp; Penghapusan</option>
                <option value="privacy-changes">7. Perubahan Kebijakan</option>
            </select>
        </div>

        <div class="grid min-h-0 flex-1 grid-cols-1 lg:grid-cols-[16rem_minmax(0,1fr)]">
            <aside class="hidden overflow-y-auto border-r border-slate-200 bg-slate-50 p-5 lg:block">
                @include('legal.partials._privacy-toc')
            </aside>
            <div class="overflow-y-auto px-5 py-6 sm:px-8 sm:py-8" data-privacy-modal-scroll>
                @include('legal.partials._privacy-content')
            </div>
        </div>

        <footer class="flex flex-none flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <a
                href="{{ route('privacy-policy.show') }}"
                class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
            >
                Buka Halaman Penuh
                <i class="fa-solid fa-arrow-up-right-from-square text-xs" aria-hidden="true"></i>
            </a>
            <button
                type="button"
                class="inline-flex min-h-11 items-center justify-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                data-privacy-modal-close
            >
                Tutup
            </button>
        </footer>
    </div>
</div>
