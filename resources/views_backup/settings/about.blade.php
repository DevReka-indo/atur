@extends('layouts.app')

@section('content')
    <div class="max-w-4xl mx-auto space-y-12">

        <div class="space-y-4">
            <h1 class="text-4xl font-semibold text-slate-800 tracking-tight">
                About the Application
            </h1>
            <p class="text-slate-500 text-lg">
                Information System and Application details.
            </p>
        </div>

        <div class="bg-white rounded-2xl shadow-md border border-slate-300 p-8 grid md:grid-cols-2 gap-10">

            <div class="space-y-2">
                <p class="text-sm text-slate-500">Application</p>
                <p class="text-lg font-semibold text-slate-800">
                    Management Proyek
                </p>
            </div>

            <div class="space-y-2">
                <p class="text-sm text-slate-500">Versi</p>
                <p class="text-lg font-semibold text-slate-800">
                    v1.2.1
                </p>
            </div>

            <div class="space-y-2">
                <p class="text-sm text-slate-500">Support</p>
                <p class="text-lg font-semibold text-slate-800">
                    infobeasiswa@gmail.com
                </p>
            </div>

            <div class="space-y-2">
                <p class="text-sm text-slate-500">Environment</p>
                <p class="text-lg font-semibold text-indigo-600">
                    Production
                </p>
            </div>

        </div>

    </div>
@endsection
