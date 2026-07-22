@extends('layouts.app')

@section('title', 'Permissions')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="w-full px-4 md:px-8">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6">
            <div>
                <h1 class="text-3xl md:text-4xl font-semibold text-slate-900">Permissions</h1>
                <p class="mt-2 text-sm text-gray-500">Daftar permission global yang tersedia untuk role aplikasi.</p>
            </div>
            @can('permissions.create')
                <a href="{{ route('management-permissions.create') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl bg-sky-600 text-white text-sm font-semibold hover:bg-sky-700 transition-colors">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Permission
                </a>
            @endcan
        </div>

        <x-management-access-tabs active="permissions" />

        @if (session('success'))
            <div class="mb-6 flex items-center gap-2 p-4 rounded-xl border border-green-200 bg-green-50 text-green-700">
                <i class="fa-solid fa-circle-check"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        <div class="mb-6 flex items-start gap-3 p-4 rounded-xl border border-amber-200 bg-amber-50 text-amber-800">
            <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
            <p class="text-sm">Permission baru belum otomatis mengamankan fitur. Developer tetap perlu menggunakannya pada route, controller, policy, atau tampilan.</p>
        </div>

        <form method="GET" action="{{ route('management-permissions.index') }}"
            class="grid grid-cols-1 md:grid-cols-[minmax(0,1fr)_minmax(220px,320px)_auto] gap-3 mb-6">
            <label class="relative">
                <span class="sr-only">Cari permission</span>
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari nama permission..."
                    class="w-full pl-10 pr-4 py-2.5 border border-gray-200 bg-white rounded-xl focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
            </label>
            <label>
                <span class="sr-only">Filter group</span>
                <select name="group"
                    class="w-full px-4 py-2.5 border border-gray-200 bg-white rounded-xl focus:ring-2 focus:ring-sky-500/30 focus:border-sky-500">
                    <option value="">Semua Group</option>
                    @foreach ($groups as $groupOption)
                        <option value="{{ $groupOption }}" @selected($group === $groupOption)>{{ str($groupOption)->headline() }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit"
                class="inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-slate-700 text-white text-sm font-semibold hover:bg-slate-800 transition-colors">
                Filter
            </button>
        </form>

        <div class="overflow-hidden bg-white border border-gray-200 rounded-2xl shadow-sm">
            <div class="flex items-center justify-between gap-4 px-5 py-4 border-b border-gray-200">
                <h2 class="font-semibold text-slate-800">Daftar Permission</h2>
                <span class="text-sm text-gray-500">Total: {{ $permissions->total() }}</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm text-left">
                    <thead class="bg-sky-100 text-xs uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-5 py-4">Technical Name</th>
                            <th class="px-5 py-4">Group</th>
                            <th class="px-5 py-4">Action</th>
                            <th class="px-5 py-4">Dipakai Role</th>
                            <th class="px-5 py-4 text-right">Jumlah Role</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($permissions as $item)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-4 font-mono text-xs font-semibold text-sky-700">{{ $item['permission']->name }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ str($item['module'])->headline() }}</td>
                                <td class="px-5 py-4 text-gray-700">{{ str($item['action'])->headline() }}</td>
                                <td class="px-5 py-4">
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($item['permission']->roles as $role)
                                            <span class="px-2 py-1 rounded-full bg-gray-100 border border-gray-200 text-xs text-gray-600">{{ str($role->name)->headline() }}</span>
                                        @empty
                                            <span class="text-xs text-gray-400">Belum digunakan</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-slate-700">{{ $item['permission']->roles_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-key text-3xl"></i>
                                    <p class="mt-3 font-medium">Permission tidak ditemukan.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($permissions->hasPages())
                <div class="px-5 py-4 border-t border-gray-200 bg-gray-50">
                    {{ $permissions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
