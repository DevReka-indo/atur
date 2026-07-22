@extends('layouts.app')

@section('title', 'Permission ' . $roleLabel)

@section('content')
    @php
        $checkedPermissionNames = collect(old('permissions', $selectedPermissionNames));
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="w-full px-4 md:px-8">
        <nav class="flex items-center gap-2 mb-5 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('management-roles.index') }}" class="hover:text-sky-700 transition-colors">
                Role &amp; Permissions
            </a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
            <span class="font-medium text-gray-700">{{ $roleLabel }}</span>
        </nav>

        <x-management-access-tabs active="roles" />

        <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-6">
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-3xl md:text-4xl font-semibold text-slate-900">{{ $roleLabel }}</h1>
                    @if ($role->name === 'super_admin')
                        <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full">
                            <i class="fa-solid fa-crown"></i>
                            Full Access
                        </span>
                    @endif
                </div>
                <p class="mt-2 text-sm text-gray-500">{{ $roleDescription }}</p>
                <p class="mt-2 text-sm font-medium text-slate-700">
                    {{ $checkedPermissionNames->count() }} permission aktif
                </p>
            </div>

            <a href="{{ route('management-roles.index') }}"
                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>
        </div>

        @if (session('success'))
            <div class="mb-6 flex items-center gap-2 p-4 rounded-xl border border-green-200 bg-green-50 text-green-700">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-6 p-4 rounded-xl border border-red-200 bg-red-50 text-red-700">
                <div class="flex items-center gap-2 font-semibold">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    Permission belum dapat disimpan.
                </div>
                <ul class="mt-2 ml-5 list-disc text-sm space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($role->name === 'super_admin')
            <div class="mb-6 p-4 rounded-xl border border-sky-200 bg-sky-50 text-sky-800">
                <div class="flex items-start gap-3">
                    <i class="fa-solid fa-shield-halved mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Super Admin memiliki seluruh akses secara otomatis melalui sistem.</p>
                        <p class="mt-1 text-sm text-sky-700">Checkbox berikut hanya menunjukkan akses efektif. Perubahan checkbox tidak berlaku karena Gate::before().</p>
                    </div>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('management-roles.update', $role) }}">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
                @forelse ($permissionGroups as $groupLabel => $groupPermissions)
                    <section class="overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-sm"
                        data-permission-group>
                        <header class="flex items-center justify-between gap-4 px-5 py-4 bg-sky-50 border-b border-sky-100">
                            <div>
                                <h2 class="font-semibold text-slate-800">{{ $groupLabel }}</h2>
                                <p class="mt-0.5 text-xs text-gray-500">{{ $groupPermissions->count() }} permission</p>
                            </div>

                            @if ($canUpdateRole)
                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-sky-800 cursor-pointer">
                                    <input type="checkbox" data-select-all
                                        class="w-4 h-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500">
                                    Pilih Semua
                                </label>
                            @endif
                        </header>

                        <div class="divide-y divide-gray-100">
                            @foreach ($groupPermissions as $item)
                                <label class="flex items-center gap-3 px-5 py-4 {{ $canUpdateRole ? 'cursor-pointer hover:bg-gray-50' : 'cursor-default' }} transition-colors">
                                    <input type="checkbox" name="permissions[]" value="{{ $item['permission']->name }}"
                                        data-permission-checkbox
                                        @checked($checkedPermissionNames->contains($item['permission']->name))
                                        @disabled(!$canUpdateRole)
                                        class="w-4 h-4 rounded border-gray-300 text-sky-600 focus:ring-sky-500 disabled:opacity-60">
                                    <span class="min-w-0">
                                        <span class="block text-sm font-semibold text-gray-800">{{ $item['action_label'] }}</span>
                                        <span class="block mt-0.5 text-xs font-mono text-gray-400 break-all">{{ $item['permission']->name }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </section>
                @empty
                    <div class="xl:col-span-2 p-10 text-center bg-white border border-dashed border-gray-300 rounded-2xl">
                        <i class="fa-solid fa-key text-3xl text-gray-300"></i>
                        <p class="mt-3 text-sm text-gray-500">Belum ada permission untuk guard web.</p>
                    </div>
                @endforelse
            </div>

            @if ($canUpdateRole)
                <div class="sticky bottom-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3 mt-6 p-4 bg-white/95 backdrop-blur border border-gray-200 rounded-2xl shadow-lg">
                    <a href="{{ route('management-roles.index') }}"
                        class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                        Batal
                    </a>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 transition-colors">
                        <i class="fa-solid fa-floppy-disk"></i>
                        Simpan Permission
                    </button>
                </div>
            @endif
        </form>
    </div>
@endsection
