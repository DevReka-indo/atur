@extends('layouts.app')

@section('title', 'Tambah Permission')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="w-full px-4 md:px-8">
        <nav class="flex items-center gap-2 mb-5 text-sm text-gray-500" aria-label="Breadcrumb">
            <a href="{{ route('management-permissions.index') }}" class="hover:text-sky-700 transition-colors">Permissions</a>
            <i class="fa-solid fa-chevron-right text-[10px] text-gray-300"></i>
            <span class="font-medium text-gray-700">Tambah Permission</span>
        </nav>

        <x-management-access-tabs active="permissions" />

        <div class="mb-6">
            <h1 class="text-3xl md:text-4xl font-semibold text-slate-900">Tambah Permission</h1>
            <p class="mt-2 text-sm text-gray-500">Buat permission teknis baru untuk digunakan developer pada fitur aplikasi.</p>
        </div>

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

        <form method="POST" action="{{ route('management-permissions.store') }}"
            class="max-w-3xl p-6 bg-white border border-gray-200 rounded-2xl shadow-sm space-y-5">
            @csrf

            <div>
                <label for="module" class="block text-sm font-semibold text-gray-800">Module</label>
                <input id="module" name="module" type="text" value="{{ old('module') }}" data-permission-module
                    required maxlength="125" placeholder="Contoh: project-reports"
                    class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
                <p class="mt-1 text-xs text-gray-500">Module akan dinormalisasi menjadi lowercase dash-case.</p>
            </div>

            <div>
                <label for="action" class="block text-sm font-semibold text-gray-800">Action</label>
                <select id="action" name="action" data-permission-action required
                    class="mt-2 w-full px-4 py-3 border border-gray-300 bg-white rounded-xl focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(old('action', 'view') === $action)>{{ str($action)->headline() }}</option>
                    @endforeach
                </select>
            </div>

            <div data-custom-action-wrapper class="hidden">
                <label for="custom_action" class="block text-sm font-semibold text-gray-800">Custom Action</label>
                <input id="custom_action" name="custom_action" type="text" value="{{ old('custom_action') }}"
                    data-permission-custom-action maxlength="125" placeholder="Contoh: publish-report"
                    class="mt-2 w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
            </div>

            <div class="p-4 rounded-xl bg-sky-50 border border-sky-100">
                <p class="text-xs font-semibold uppercase tracking-wide text-sky-700">Technical Permission Name</p>
                <code class="block mt-2 font-mono text-sm text-sky-900 break-all" data-permission-name-preview>module.view</code>
                <p class="mt-2 text-xs text-sky-700">Guard permission selalu <strong>web</strong>.</p>
            </div>

            <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2">
                <a href="{{ route('management-permissions.index') }}"
                    class="inline-flex items-center justify-center px-5 py-2.5 rounded-xl border border-gray-200 text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">Batal</a>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 transition-colors">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Simpan Permission
                </button>
            </div>
        </form>
    </div>
@endsection
