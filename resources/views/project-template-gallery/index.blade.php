@extends('layouts.app')
@section('title', 'Template Gallery')

@section('content')
    <div class="w-full px-4 py-5 md:px-8">
        <div class="mb-6">
            <div class="flex items-center gap-3">
                <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-sky-500 to-indigo-600 text-white shadow-lg shadow-sky-500/20">
                    <i class="fa-solid fa-table-cells-large"></i>
                </span>
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Template Gallery</h1>
                    <p class="mt-1 text-sm text-slate-500">Jelajahi dan gunakan template project yang tersedia.</p>
                </div>
            </div>
        </div>

        <form method="GET" action="{{ route('template-gallery.index') }}"
            class="mb-6 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_240px_auto_auto]">
            <label class="sr-only" for="gallery-search">Cari template</label>
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input id="gallery-search" type="search" name="search" value="{{ $search }}"
                    placeholder="Cari nama, deskripsi, atau kategori..."
                    class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm focus:border-sky-500 focus:ring-sky-500">
            </div>

            <label class="sr-only" for="gallery-category">Filter kategori</label>
            <select id="gallery-category" name="category"
                class="rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                <option value="">Semua kategori</option>
                @foreach ($categories as $category)
                    <option value="{{ $category->id }}" @selected($categoryId === $category->id)>
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-700">
                <i class="fa-solid fa-magnifying-glass"></i>
                Cari
            </button>
            <a href="{{ route('template-gallery.index') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                <i class="fa-solid fa-rotate-left"></i>
                Reset Filter
            </a>
        </form>

        @if ($templates->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-xl text-slate-400">
                    <i class="fa-solid fa-folder-open"></i>
                </span>
                <h2 class="mt-4 text-lg font-semibold text-slate-900">Tidak ada template yang sesuai.</h2>
                <p class="mt-1 text-sm text-slate-500">Ubah kata pencarian atau reset filter untuk melihat template lain.</p>
                <a href="{{ route('template-gallery.index') }}"
                    class="mt-5 inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset Filter
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                @foreach ($templates as $template)
                    @include('project-template-gallery.partials._template-card', [
                        'template' => $template,
                        'summary' => $summaries->get($template->id),
                    ])
                @endforeach
            </div>

            @if ($templates->hasPages())
                <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                    {{ $templates->links() }}
                </div>
            @endif
        @endif
    </div>

    @include('project-template-gallery.partials._use-template-modal')
@endsection
