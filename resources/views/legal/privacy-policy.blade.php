@extends('layouts.guest')

@section('title', 'Kebijakan Privasi')

@section('content')
    <main class="min-h-screen w-full bg-slate-50 px-4 py-6 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-6xl">
            <header class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
                <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex items-start gap-4">
                        {{-- <span class="flex h-12 w-12 flex-none items-center justify-center rounded-xl bg-blue-50 text-blue-700">
                            <i class="fa-solid fa-shield-halved" aria-hidden="true"></i>
                        </span> --}}
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Dokumen Legal ATUR</p>
                            <h1 class="mt-2 text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Kebijakan Privasi</h1>
                            <p class="mt-2 text-sm text-slate-500">
                                Versi {{ config('atur.privacy_version') }} · Berlaku sejak {{ config('atur.privacy_effective_date') }}
                            </p>
                        </div>
                    </div>
                    <a
                        href="{{ auth()->check() ? route('settings.about') : route('home') }}"
                        class="inline-flex min-h-11 items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                    >
                        <i class="fa-solid fa-arrow-left text-xs" aria-hidden="true"></i>
                        {{ auth()->check() ? 'Tentang Aplikasi' : 'Kembali ke Login' }}
                    </a>
                </div>
            </header>

            <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-[16rem_minmax(0,1fr)]">
                <aside class="hidden self-start rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-6 lg:block">
                    @include('legal.partials._privacy-toc')
                </aside>

                <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8 lg:p-10">
                    <div class="mb-8 lg:hidden">
                        <label for="privacy-page-jump" class="mb-2 block text-sm font-semibold text-slate-700">
                            Lompat ke bagian
                        </label>
                        <select
                            id="privacy-page-jump"
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

                    @include('legal.partials._privacy-content')
                </article>
            </div>

            <p class="py-8 text-center text-xs text-slate-500">
                © {{ now()->year }} {{ config('atur.application_name') }} · {{ config('atur.developer') }}.
            </p>
        </div>
    </main>
@endsection
