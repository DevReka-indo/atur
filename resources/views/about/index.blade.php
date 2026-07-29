@extends('layouts.app')

@section('title', 'Tentang Aplikasi')

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-slate-50 to-blue-50/40"></div>

    <main class="mx-auto w-full max-w-7xl pb-2" data-about-page>
        <div class="flex flex-col gap-8">
            @include('about.partials._hero')
            @include('about.partials._features')

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    @include('about.partials._privacy-card')
                </div>
                @include('about.partials._support')
            </div>
        </div>

        <p class="mt-8 text-center text-xs text-slate-500">
            © {{ now()->year }} {{ config('atur.application_name') }} · {{ config('atur.developer') }}.
            Penggunaan internal.
        </p>

        @include('about.partials._privacy-modal')
    </main>
@endsection
