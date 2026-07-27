@extends('layouts.app')
@section('title', 'Buat Project Template')
@section('content')
    <div class="mx-auto max-w-4xl px-4 py-4 sm:px-6 lg:px-8">
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ route('project-templates.index') }}"
                class="hover:text-sky-700">Project Templates</a><i
                class="fa-solid fa-chevron-right mx-2 text-xs"></i><span>Buat</span></nav>
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
            <h1 class="text-2xl font-bold text-slate-900">Buat Project Template</h1>
            <p class="mt-1 mb-6 text-sm text-slate-500">Template baru selalu dibuat tidak aktif hingga graph dan weight
                valid.</p>
            <form method="POST" action="{{ route('project-templates.store') }}">@csrf @include('project-templates.partials._form')</form>
        </div>
    </div>
@endsection
