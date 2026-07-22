@extends('layouts.app')

@section('title', 'Kelola user')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="w-full px-4 md:px-8">

        {{-- HEADER --}}
        <div class="mb-6">
            <div class="flex items-center gap-2 mb-4">
                <h1 class="text-4xl font-semibold text-slate-900">
                    Management Users
                </h1>
                <button onclick="openManageUserModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="text-gray-500 mb-4">Manage system users and access permissions</p>

            {{-- Search & Total - Sebaris --}}
            <div class="flex items-center justify-between gap-4">
                {{-- Search Form --}}
                <form method="GET" action="{{ route('management-users.index') }}" id="search-form"
                    class="flex-1 max-w-md">
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                        <input type="text" name="search" id="search-input" value="{{ $search ?? '' }}"
                            placeholder="Search name or email..." autocomplete="off"
                            class="pl-9 pr-4 py-2.5 w-full rounded-xl border border-gray-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition-all duration-200" />

                        {{-- AUTOCOMPLETE DROPDOWN --}}
                        <div id="autocomplete-list"
                            class="absolute z-50 w-full bg-white border border-gray-200 rounded-xl shadow-lg mt-1 hidden">
                            <div
                                class="px-3 py-2 text-xs font-semibold text-gray-400 uppercase tracking-wider border-b">
                                Users</div>
                            <ul id="autocomplete-items" class="py-1 max-h-48 overflow-y-auto"></ul>
                        </div>
                    </div>

                    <input type="hidden" name="sort" value="{{ request('sort', 'name') }}">
                    <input type="hidden" name="direction" value="{{ request('direction', 'asc') }}">

                    @if (!empty($search))
                        <a href="{{ route('management-users.index') }}"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                            <i class="fa-solid fa-xmark text-sm"></i>
                        </a>
                    @endif
                </form>

                {{-- Total Badge - Di Kanan --}}
                <div class="flex-shrink-0 flex items-center gap-3">
                    @can('management-users.create')
                        <a href="{{ route('management-users.create') }}"
                            class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-colors">
                            <i class="fa-solid fa-user-plus"></i>
                            Add User
                        </a>
                    @endcan
                    <div class="px-4 py-2 bg-white rounded-xl shadow-sm border border-gray-200 text-sm text-gray-600">
                        Total: <span class="font-semibold text-gray-800">{{ $users->total() }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- FLASH MESSAGE --}}
        @if (session('success'))
            <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                {{ session('success') }}
            </div>
        @endif

        {{-- Table Card --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left">
                    @php
                        $currentSort = request('sort', 'name');
                        $currentDir = request('direction', 'asc');

                        $sortableHeader = function ($label, $column) use ($currentSort, $currentDir) {
                            $isActive = $currentSort === $column;
                            $nextDir = $isActive && $currentDir === 'asc' ? 'desc' : 'asc';
                            $url = request()->fullUrlWithQuery(['sort' => $column, 'direction' => $nextDir]);

                            $icon = $isActive
                                ? ($currentDir === 'asc'
                                    ? '<i class="fa-solid fa-arrow-up text-indigo-500 ml-1"></i>'
                                    : '<i class="fa-solid fa-arrow-down text-indigo-500 ml-1"></i>')
                                : '<i class="fa-solid fa-arrows-up-down text-gray-300 ml-1"></i>';

                            return "<a href=\"{$url}\" class=\"flex items-center gap-1 hover:text-indigo-600 transition-colors\">{$label}{$icon}</a>";
                        };
                    @endphp

                    <thead>
                        <tr class="bg-[#ADE8F4] text-gray-700 uppercase text-xs tracking-wider">
                            <th class="px-6 py-4">No</th>
                            <th class="px-6 py-4">{!! $sortableHeader('User', 'name') !!}</th>
                            <th class="px-6 py-4">{!! $sortableHeader('Email', 'email') !!}</th>
                            <th class="px-6 py-4">Role</th>
                            <th class="px-6 py-4">Job Title</th>
                            <th class="px-6 py-4">Departemen</th>
                            <th class="px-6 py-4">Created</th>
                            <th class="px-6 py-4 text-right">Actions</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100">
                        @forelse ($users as $user)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-400">
                                    {{ $loop->iteration + ($users->currentPage() - 1) * $users->perPage() }}
                                </td>

                                {{-- User Name --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        @if ($user->profile_photo)
                                            <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                                class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-gray-200">
                                        @else
                                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-sm font-bold text-indigo-600">
                                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                    </div>
                                </td>

                                {{--Email --}}
                                <td class="px-6 py-4">
                                    <span class="text-sm text-gray-600 truncate block max-w-[200px]" title="{{ $user->email }}">
                                        {{ $user->email }}
                                    </span>
                                </td>

                                {{-- Role --}}
                                <td class="px-6 py-4">
                                    @php
                                        $roles = [
                                            'super_admin' => [
                                                'label' => 'Super Admin',
                                                'class' => 'bg-gradient-to-r from-red-500 to-rose-600 text-white shadow shadow-red-200',
                                                'icon' => 'fa-solid fa-crown',
                                            ],
                                            'member' => [
                                                'label' => 'Member',
                                                'class' => 'bg-gray-100 text-gray-600 border border-gray-200',
                                                'icon' => 'fa-solid fa-user',
                                            ],
                                            'contributor' => [
                                                'label' => 'Contributor',
                                                'class' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                                'icon' => 'fa-solid fa-pen-ruler',
                                            ],
                                        ];
                                        $role = $roles[$user->role] ?? [
                                            'label' => str($user->role)->headline()->toString(),
                                            'class' => 'bg-violet-100 text-violet-700 border border-violet-200',
                                            'icon' => 'fa-solid fa-user-tag',
                                        ];
                                    @endphp
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full {{ $role['class'] }}">
                                        <i class="{{ $role['icon'] }} text-[11px]"></i>
                                        {{ $role['label'] }}
                                    </span>
                                </td>

                                {{-- Job Title --}}
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-semibold text-gray-700">
                                        {{ $user->job_title ?? '-' }}
                                    </span>
                                </td>

                                {{-- Departemen --}}
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 text-xs font-semibold text-gray-700">
                                        {{ $user->department ?? '-' }}
                                    </span>
                                </td>

                                {{-- Created --}}
                                <td class="px-6 py-4 text-gray-500">
                                    <div>{{ $user->created_at->format('d M Y') }}</div>
                                    <div class="text-xs text-gray-400 mt-0.5">{{ $user->created_at->diffForHumans() }}</div>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex justify-end items-center gap-2">
                                        @if ($user->id === Auth::id())
                                            <span class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-600">
                                                You
                                            </span>
                                        @else
                                            @can('management-users.update')
                                                {{-- Edit --}}
                                                <a href="{{ route('management-users.edit', $user) }}" title="Edit"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>
                                            @endcan

                                            @can('management-users.toggle-status')
                                                <form method="POST" action="{{ route('management-users.toggle-status', $user) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" title="{{ $user->is_active ? 'Deactivate' : 'Activate' }}"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg {{ $user->is_active ? 'text-orange-500 hover:bg-orange-50' : 'text-emerald-500 hover:bg-emerald-50' }} transition-colors cursor-pointer">
                                                        <i class="fa-solid fa-power-off"></i>
                                                    </button>
                                                </form>
                                            @endcan

                                            @can('management-users.delete')
                                                {{-- Delete --}}
                                                <form method="POST" action="{{ route('management-users.destroy', $user) }}"
                                                    onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="Delete"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-400">
                                    <i class="fa-solid fa-users-slash text-3xl mb-3 block"></i>
                                    <div class="text-lg font-medium">
                                        @if (!empty($search))
                                            No users found for "{{ $search }}"
                                        @else
                                            No users found
                                        @endif
                                    </div>
                                    <div class="text-sm mt-1">
                                        @if (!empty($search))
                                            <a href="{{ route('management-users.index') }}" class="text-indigo-500 hover:underline">Reset search</a>
                                        @else
                                            Try adding a new user
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- PAGINATION --}}
            <div class="px-6 py-4 bg-gray-50 border-t">
                {{ $users->appends(request()->query())->links() }}
            </div>
        </div>

    </div>

    @push('scripts')
        <script>
            const searchInput = document.getElementById('search-input');
            const autocompleteList = document.getElementById('autocomplete-list');
            const autocompleteItems = document.getElementById('autocomplete-items');
            let debounceTimer;

            searchInput.addEventListener('input', function() {
                const query = this.value.trim();
                clearTimeout(debounceTimer);

                if (query.length < 2) {
                    autocompleteList.classList.add('hidden');
                    return;
                }

                debounceTimer = setTimeout(() => {
                    fetch(`{{ route('management-users.index') }}?search=${encodeURIComponent(query)}`, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            autocompleteItems.innerHTML = '';

                            if (data.length === 0) {
                                autocompleteList.classList.add('hidden');
                                return;
                            }

                            data.forEach(user => {
                                const li = document.createElement('li');
                                li.className =
                                    'px-4 py-2.5 hover:bg-indigo-50 cursor-pointer flex items-center gap-3 transition-colors';
                                li.innerHTML = `
                            <div class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                <span class="text-xs font-bold text-indigo-600">${user.name.charAt(0).toUpperCase()}</span>
                            </div>
                            <div class="text-sm font-medium text-gray-800">${user.name}</div>
                        `;
                                li.addEventListener('click', () => {
                                    searchInput.value = user.name;
                                    autocompleteList.classList.add('hidden');
                                    document.getElementById('search-form').submit();
                                });
                                autocompleteItems.appendChild(li);
                            });

                            autocompleteList.classList.remove('hidden');
                        });
                }, 300);
            });

            document.addEventListener('click', function(e) {
                if (!searchInput.contains(e.target) && !autocompleteList.contains(e.target)) {
                    autocompleteList.classList.add('hidden');
                }
            });
        </script>
    @endpush


    <!-- POP UPNYA BOSCUUU  -->
    <div id="manageUserModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">
                Tentang Management Users
            </h2>

            <div class="space-y-5 text-sm text-slate-600">

                <p class="leading-relaxed">
                    Management Users digunakan untuk mengelola seluruh pengguna dalam sistem,
                    termasuk pengaturan data pengguna, peran, serta hak akses dalam platform.
                </p>

                <div class="border-t pt-4 space-y-4">

                    <div class="flex items-start gap-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Pengelolaan data pengguna</span><br>
                            <span class="text-slate-500">Menambah, mengedit, dan menghapus data user sesuai
                                kebutuhan.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Manajemen role & hak akses</span><br>
                            <span class="text-slate-500">Menentukan peran seperti admin, user, atau lainnya sesuai
                                kebutuhan sistem.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Keamanan akun</span><br>
                            <span class="text-slate-500">Mengontrol akses login dan menjaga keamanan data pengguna.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Monitoring aktivitas user</span><br>
                            <span class="text-slate-500">Melihat aktivitas pengguna untuk memastikan penggunaan sistem
                                berjalan dengan baik.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Efisiensi pengelolaan</span><br>
                            <span class="text-slate-500">Semua pengguna dapat dikelola dalam satu halaman secara
                                terpusat.</span>
                        </p>
                    </div>

                </div>
            </div>

            <!-- BUTTON -->
            <div class="flex justify-end gap-2 mt-6">
                <button onclick="confirmManageUser()"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Selesai
                </button>
            </div>

        </div>
    </div>


    <!-- SCRIPTNYA LEK KU -->
    <script>
        window.openManageUserModal = function() {
            const modal = document.getElementById('manageUserModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        window.closeManageUserModal = function() {
            const modal = document.getElementById('manageUserModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        window.confirmManageUser = function() {
            closeManageUserModal();
            console.log("User lanjut management user");
        }
    </script>
@endsection
