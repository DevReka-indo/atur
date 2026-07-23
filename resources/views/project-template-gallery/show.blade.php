@extends('layouts.app')
@section('title', $projectTemplate->name)

@section('content')
    <div class="w-full px-4 py-5 md:px-8">
        <nav class="mb-5 flex items-center gap-2 text-sm text-slate-500" aria-label="Breadcrumb">
            <a href="{{ route('template-gallery.index') }}" class="transition hover:text-sky-700">Template Gallery</a>
            <i class="fa-solid fa-chevron-right text-[10px] text-slate-400"></i>
            <span class="truncate font-medium text-slate-700">{{ $projectTemplate->name }}</span>
        </nav>

        <section class="mb-6 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="h-1.5 bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500"></div>
            <div class="flex flex-col gap-5 p-6 lg:flex-row lg:items-start lg:justify-between">
                <div class="min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700">
                            {{ $preview['category'] }}
                        </span>
                        <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                            Version {{ $preview['version'] }}
                        </span>
                    </div>
                    <h1 class="mt-3 break-words text-3xl font-bold text-slate-900">{{ $preview['name'] }}</h1>
                    <p class="mt-2 max-w-3xl text-sm leading-6 text-slate-600">
                        {{ $preview['description'] ?: 'Template ini belum memiliki deskripsi.' }}
                    </p>
                </div>

                <div class="flex flex-col gap-2 sm:flex-row lg:shrink-0">
                    <a href="{{ route('template-gallery.index') }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-left"></i>
                        Kembali ke Gallery
                    </a>
                    <a href="{{ route('projects.create', ['project_template_id' => $projectTemplate->id]) }}"
                        class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-sky-700">
                        <i class="fa-solid fa-arrow-right"></i>
                        Gunakan Template
                    </a>
                </div>
            </div>
        </section>

        @include('project-template-gallery.partials._template-summary', [
            'summary' => $preview['summary'],
        ])

        <section class="mt-6 rounded-2xl border border-slate-200 bg-slate-50 p-4 shadow-sm sm:p-6">
            <div class="mb-4">
                <h2 class="text-lg font-bold text-slate-900">Hierarchy dan Detail Task</h2>
                <p class="mt-1 text-sm text-slate-500">Offset, duration, beban relatif, dan dependency berasal dari kalkulasi template yang sama dengan Create Project.</p>
            </div>

            <div class="space-y-2">
                @foreach ($preview['tasks'] as $taskNode)
                    @include('project-template-gallery.partials._template-tree', ['taskNode' => $taskNode])
                @endforeach
            </div>
        </section>
    </div>
@endsection
