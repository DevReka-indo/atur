@extends('layouts.app')
@section('title', $template->name)
@section('content')
    <div class="w-full px-4 py-4 md:px-8" data-confirmation-scope>
        <nav class="mb-4 text-sm text-slate-500"><a href="{{ route('project-templates.index') }}" class="hover:text-sky-700">Project Templates</a><i class="fa-solid fa-chevron-right mx-2 text-xs"></i><span>{{ $template->name }}</span></nav>
        @if(session('success'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

        <div class="mb-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between"><div><div class="mb-2 flex flex-wrap items-center gap-2">@include('project-templates.partials._status-badge')<span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold text-indigo-700">Version {{ $template->version }}</span></div><h1 class="text-3xl font-bold text-slate-900">{{ $template->name }}</h1><p class="mt-1 text-sm text-slate-500">{{ $template->category->name }} · {{ $template->description ?: 'Tanpa deskripsi' }}</p></div>
                <div class="flex flex-wrap gap-2">
                    @can('project-templates.update')<a href="{{ route('project-templates.edit', $template) }}" class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700"><i class="fa-solid fa-pen mr-2"></i>Edit Metadata</a><form method="POST" action="{{ route('project-templates.toggle-status', $template) }}">@csrf @method('PATCH')<input type="hidden" name="is_active" value="{{ $template->is_active ? 0 : 1 }}"><button class="rounded-xl bg-sky-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-power-off mr-2"></i>{{ $template->is_active ? 'Nonaktifkan' : 'Aktifkan' }}</button></form>@endcan
                    @can('project-templates.delete')<form method="POST" action="{{ route('project-templates.destroy', $template) }}" data-confirm="Hapus template ini? Project lama tidak akan berubah.">@csrf @method('DELETE')<button class="rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white"><i class="fa-solid fa-trash mr-2"></i>Hapus</button></form>@endcan
                </div>
            </div>
            <div class="mt-5 grid gap-3 sm:grid-cols-3"><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Total Task</p><p class="text-2xl font-bold">{{ $tasks->count() }}</p></div><div class="rounded-xl bg-slate-50 p-4"><p class="text-xs text-slate-500">Leaf Task</p><p class="text-2xl font-bold">{{ $leafTasks->count() }}</p></div>@include('project-templates.partials._weight-summary')</div>
        </div>

        @if($warnings->isNotEmpty())<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"><p class="font-semibold"><i class="fa-solid fa-triangle-exclamation mr-2"></i>Template belum siap diaktifkan</p><ul class="mt-2 list-disc pl-5">@foreach($warnings->unique() as $warning)<li>{{ $warning }}</li>@endforeach</ul></div>@endif
        <div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">@include('project-templates.partials.tasks._tree') @include('project-templates.partials._schedule-preview')</div>
    </div>
@endsection
