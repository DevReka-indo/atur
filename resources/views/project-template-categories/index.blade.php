@extends('layouts.app')
@section('title', 'Kategori Project Template')

@section('content')
    <div class="w-full px-4 py-4 md:px-8">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Kategori Project Template</h1>
                <p class="mt-1 text-sm text-slate-500">Kelola kategori untuk template project yang dapat digunakan ulang.</p>
            </div>
            @can('project-template-categories.create')
                <a href="{{ route('project-template-categories.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-sky-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
                    <i class="fa-solid fa-plus"></i> Tambah Kategori
                </a>
            @endcan
        </div>

        @if(session('success'))<div class="mb-5 rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-700">{{ session('success') }}</div>@endif
        @if($errors->any())<div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">{{ $errors->first() }}</div>@endif

        <div class="mb-5 flex flex-wrap gap-2 border-b border-slate-200">
            @can('project-templates.view')<a href="{{ route('project-templates.index') }}" class="px-4 py-3 text-sm font-semibold text-slate-500 hover:text-sky-700">Templates</a>@endcan
            <a href="{{ route('project-template-categories.index') }}" class="border-b-2 border-sky-600 px-4 py-3 text-sm font-semibold text-sky-700">Categories</a>
        </div>

        <form method="GET" class="mb-5 grid gap-3 md:grid-cols-[1fr_220px_auto]">
            <input type="search" name="search" value="{{ $search }}" placeholder="Cari kategori..." class="rounded-xl border-slate-300">
            <select name="status" class="rounded-xl border-slate-300">
                <option value="">Semua status</option>
                <option value="active" @selected($status === 'active')>Aktif</option>
                <option value="inactive" @selected($status === 'inactive')>Tidak aktif</option>
            </select>
            <button class="rounded-xl bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white">Filter</button>
        </form>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-left text-sm">
                    <thead class="bg-sky-100 text-xs uppercase text-slate-600"><tr><th class="px-5 py-4">Nama</th><th class="px-5 py-4">Status</th><th class="px-5 py-4">Template</th><th class="px-5 py-4">Creator</th><th class="px-5 py-4 text-right">Aksi</th></tr></thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categories as $category)
                            <tr class="hover:bg-slate-50">
                                <td class="px-5 py-4"><p class="font-semibold text-slate-900">{{ $category->name }}</p><p class="text-xs text-slate-400">{{ $category->slug }}</p></td>
                                <td class="px-5 py-4"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $category->is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ $category->is_active ? 'Aktif' : 'Tidak aktif' }}</span></td>
                                <td class="px-5 py-4">{{ $category->templates_count }}</td>
                                <td class="px-5 py-4">{{ $category->creator?->name ?? '—' }}</td>
                                <td class="px-5 py-4"><div class="flex justify-end gap-2">
                                    @can('project-template-categories.update')
                                        <a href="{{ route('project-template-categories.edit', $category) }}" class="rounded-lg p-2 text-amber-600 hover:bg-amber-50" title="Edit"><i class="fa-solid fa-pen"></i></a>
                                        <form method="POST" action="{{ route('project-template-categories.toggle-status', $category) }}">@csrf @method('PATCH')<button class="rounded-lg p-2 text-sky-600 hover:bg-sky-50" title="Ubah status"><i class="fa-solid fa-power-off"></i></button></form>
                                    @endcan
                                    @can('project-template-categories.delete')
                                        <form method="POST" action="{{ route('project-template-categories.destroy', $category) }}" data-confirm="Hapus kategori ini?">@csrf @method('DELETE')<button class="rounded-lg p-2 text-red-600 hover:bg-red-50" title="Hapus"><i class="fa-solid fa-trash"></i></button></form>
                                    @endcan
                                </div></td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-12 text-center text-slate-400">Belum ada kategori template.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($categories->hasPages())<div class="border-t border-slate-200 p-4">{{ $categories->links() }}</div>@endif
        </div>
    </div>
@endsection
