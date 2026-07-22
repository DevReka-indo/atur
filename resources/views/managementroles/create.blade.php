@extends('layouts.app')

@section('title', 'Tambah Role')

@section('content')
    @php
        $checkedPermissionNames = collect(old('permissions', []));
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="w-full px-4 md:px-8">
        <nav class="flex items-center gap-2 mb-5 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('management-roles.index') }}" class="hover:text-sky-700 transition-colors">Role &amp; Permissions</a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
            <span class="font-medium text-gray-700">Tambah Role</span>
        </nav>

        <x-management-access-tabs active="roles" />

        <div class="mb-6">
            <h1 class="text-3xl md:text-4xl font-semibold text-slate-900">Tambah Role</h1>
            <p class="mt-2 text-sm text-gray-500">Buat role global baru dan pilih permission awalnya.</p>
        </div>

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
                <div class="flex items-center gap-2 font-semibold">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Role belum dapat disimpan.
                </div>
                <ul class="mt-2 ml-5 list-disc text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('management-roles.store') }}">
            @csrf

            <section class="mb-6 p-5 bg-white border border-gray-200 rounded-2xl shadow-sm">
                <label for="display_name" class="block text-sm font-semibold text-gray-800">Nama Role</label>
                <input id="display_name" name="display_name" type="text" value="{{ old('display_name') }}"
                    data-role-name-input required maxlength="125" placeholder="Contoh: Template Reviewer"
                    class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
                <p class="mt-2 text-xs text-gray-500">
                    Nama teknis: <code class="font-mono text-sky-700" data-role-name-preview>belum_diisi</code>
                </p>
                <p class="mt-3 text-sm text-gray-500">Role dan permission dapat dikelola kembali setelah role dibuat.</p>
            </section>

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                @forelse ($permissionGroups as $groupLabel => $groupPermissions)
                    <section class="overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-sm" data-permission-group>
                        <header class="flex items-center justify-between gap-4 px-5 py-4 bg-sky-50 border-b border-sky-100">
                            <div>
                                <h2 class="font-semibold text-slate-800">{{ $groupLabel }}</h2>
                                <p class="text-xs text-gray-500">{{ $groupPermissions->count() }} permission</p>
                            </div>
                            <label class="inline-flex items-center gap-2 text-xs font-semibold text-sky-800 cursor-pointer">
                                <input type="checkbox" data-select-all class="w-4 h-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                Pilih Semua
                            </label>
                        </header>
                        <div class="divide-y divide-gray-100">
                            @foreach ($groupPermissions as $item)
                                <label class="flex items-center gap-3 px-5 py-4 cursor-pointer hover:bg-gray-50 transition-colors">
                                    <input type="checkbox" name="permissions[]" value="{{ $item['permission']->name }}"
                                        data-permission-checkbox @checked($checkedPermissionNames->contains($item['permission']->name))
                                        class="w-4 h-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-800">{{ $item['action_label'] }}</span>
                                        <span class="block text-xs font-mono text-gray-400 break-all">{{ $item['permission']->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="xl:col-span-2 p-10 text-center bg-white border border-dashed border-gray-300 rounded-2xl text-gray-500">
                        Belum ada permission guard web yang dapat dipilih.
                    </div>
                @endforelse
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 p-4 bg-white border border-gray-200 rounded-2xl shadow-sm">
                <a href="{{ route('management-roles.index') }}"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Batal</a>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 transition-colors">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Simpan Role
                </button>
            </div>
        </form>
    </div>
@endsection
