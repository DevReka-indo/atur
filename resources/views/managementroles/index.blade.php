@extends('layouts.app')

@section('title', 'Role & Permissions')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="w-full px-4 md:px-8">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-11 h-11 rounded-xl bg-sky-100 text-sky-700">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <div>
                    <h1 class="text-3xl md:text-4xl font-semibold text-slate-900">Role &amp; Permissions</h1>
                    <p class="mt-1 text-sm text-gray-500">Kelola akses global yang dimiliki setiap role aplikasi.</p>
                </div>
            </div>

            @can('roles.create')
                <a href="{{ route('management-roles.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 transition-colors">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Role
                </a>
            @endcan
        </div>

        <x-management-access-tabs active="roles" />

        @if (session('success'))
            <div class="mb-6 flex items-center gap-2 p-4 rounded-xl border border-green-200 bg-green-50 text-green-700">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            @forelse ($roles as $role)
                <article class="flex flex-col p-6 bg-white border border-gray-200 rounded-2xl shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="flex flex-wrap items-center gap-2">
                                <h2 class="text-xl font-semibold text-slate-900">
                                    {{ $roleLabels[$role->name] ?? str($role->name)->headline() }}
                                </h2>
                                @if ($role->name === 'super_admin')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 text-xs font-semibold text-amber-700 bg-amber-50 border border-amber-200 rounded-full">
                                        <i class="fa-solid fa-crown"></i>
                                        Full Access
                                    </span>
                                @endif
                            </div>
                            <p class="mt-3 text-sm leading-6 text-gray-500">
                                {{ $roleDescriptions[$role->name] ?? 'Role aplikasi dengan akses yang dapat dikonfigurasi.' }}
                            </p>
                        </div>
                    </div>

                    <dl class="grid grid-cols-2 gap-3 my-6">
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Pengguna</dt>
                            <dd class="mt-1 text-2xl font-semibold text-slate-800">{{ $role->users_count }}</dd>
                        </div>
                        <div class="p-3 rounded-xl bg-gray-50 border border-gray-100">
                            <dt class="text-xs font-medium uppercase tracking-wide text-gray-400">Permission</dt>
                            <dd class="mt-1 text-2xl font-semibold text-slate-800">{{ $role->permissions_count }}</dd>
                        </div>
                    </dl>

                    @can('roles.view')
                        <a href="{{ route('management-roles.edit', $role) }}"
                            class="mt-auto inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white bg-sky-600 hover:bg-sky-700 transition-colors">
                            <i class="fa-solid {{ $role->name === 'super_admin' ? 'fa-eye' : 'fa-sliders' }}"></i>
                            {{ $role->name === 'super_admin' ? 'Lihat Akses' : 'Kelola Permission' }}
                        </a>
                    @endcan
                </article>
            @empty
                <div class="lg:col-span-3 p-10 text-center bg-white border border-dashed border-gray-300 rounded-2xl">
                    <i class="fa-solid fa-user-shield text-3xl text-gray-300"></i>
                    <h2 class="mt-3 text-lg font-semibold text-gray-700">Belum ada role</h2>
                    <p class="mt-1 text-sm text-gray-500">Jalankan seeder permission untuk menyiapkan role aplikasi.</p>
                </div>
            @endforelse
        </div>
    </div>
@endsection
