<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" aria-labelledby="about-title">
    <div class="grid grid-cols-1 lg:grid-cols-5">
        <div class="relative flex flex-col justify-between gap-6 overflow-hidden bg-slate-950 p-6 sm:p-8 lg:col-span-2">
            <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-blue-500/15 blur-3xl" aria-hidden="true"></div>
            <div class="absolute -bottom-24 -left-16 h-64 w-64 rounded-full bg-teal-500/10 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex items-center gap-4">
                <span class="flex h-16 w-16 flex-none items-center justify-center rounded-2xl bg-white shadow-sm ring-1 ring-white/20">
                    <img
                        src="{{ asset('images/Logo Badge.svg') }}"
                        alt="Logo ATUR"
                        class="h-12 w-12"
                    >
                </span>
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-300">Aplikasi Manajemen</p>
                    <p class="mt-1 text-2xl font-bold tracking-tight text-white">{{ config('atur.application_name') }}</p>
                </div>
            </div>

            <div class="relative">
                <h1 id="about-title" class="text-2xl font-bold tracking-tight text-white sm:text-3xl">
                    Tentang Aplikasi
                </h1>
                <p class="mt-3 text-base font-semibold text-blue-100">{{ config('atur.tagline') }}</p>
                <p class="mt-3 max-w-xl text-sm leading-6 text-slate-300">{{ config('atur.description') }}</p>
            </div>

            <div class="relative flex flex-wrap gap-2" aria-label="Status aplikasi">
                <span class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold text-white ring-1 ring-inset ring-white/15">
                    Versi {{ config('atur.version') }}
                </span>
                <span class="rounded-full bg-emerald-400/15 px-3 py-1.5 text-xs font-semibold text-emerald-200 ring-1 ring-inset ring-emerald-300/20">
                    {{ config('atur.environment_label') }}
                </span>
                <span class="rounded-full bg-blue-400/15 px-3 py-1.5 text-xs font-semibold text-blue-200 ring-1 ring-inset ring-blue-300/20">
                    Aplikasi Internal
                </span>
            </div>
        </div>

        <div class="p-6 sm:p-8 lg:col-span-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Informasi Aplikasi</p>
                <h2 class="mt-2 text-xl font-bold tracking-tight text-slate-950">Identitas dan detail platform</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">
                    Informasi resmi aplikasi yang digunakan untuk mendukung pengelolaan project perusahaan.
                </p>
            </div>

            @include('about.partials._metadata')
        </div>
    </div>
</section>
