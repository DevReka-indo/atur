@extends('layouts.app')

@section('title', 'Kelola user')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-7xl mx-auto">

            {{-- HEADER --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                    <p class="text-gray-500 mt-1">Manage system users and access permissions</p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-white rounded-xl shadow text-sm text-gray-600 border">
                        Total: <span class="font-semibold text-gray-800">{{ $users->total() }}</span>
                    </div>

                    {{-- search --}}
                    <form method="GET" action="{{ route('management-users.index') }}" id="search-form"
                        class="flex items-center gap-2">
                        <div class="relative">
                            <i
                                class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                            <input type="text" name="search" id="search-input" value="{{ $search ?? '' }}"
                                placeholder="Search name or email..." autocomplete="off"
                                class="pl-9 pr-4 py-2.5 w-64 rounded-xl border border-gray-200 bg-white text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400
                           transition-all duration-200" />

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
                                class="inline-flex items-center justify-center w-9 h-9 bg-gray-100 text-gray-500 rounded-xl hover:bg-gray-200 transition-colors">
                                <i class="fa-solid fa-xmark text-sm"></i>
                            </a>
                        @endif
                    </form>

                    <a href="{{ route('management-users.create') }}"
                        class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                   bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all duration-300">
                        <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                        Add User
                    </a>
                </div>
            </div>

            {{-- FLASH MESSAGE --}}
            @if (session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-50 border border-green-200 text-green-700 flex items-center gap-2">
                    <i class="fa-solid fa-circle-check"></i>
                    {{ session('success') }}
                </div>
            @endif

            {{-- table card --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
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
                                <th class="px-6 py-4">Role</th>
                                <th class="px-6 py-4">Job Tittle</th>
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
                                    <td class="px-6 py-4">
                                        <div class="flex items-center gap-3">
                                            @if ($user->profile_photo)
                                                <img src="{{ asset('storage/' . $user->profile_photo) }}"
                                                    class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-gray-200">
                                            @else
                                                <div
                                                    class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-sm font-bold text-indigo-600">
                                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <div class="font-semibold text-gray-800">{{ $user->name }}</div>
                                        </div>
                                    </td>

                                    {{-- ROLE --}}
                                    <td class="px-6 py-4">
                                        @php
                                            $roles = [
                                                'super_admin' => [
                                                    'label' => 'Super Admin',
                                                    'class' =>
                                                        'bg-gradient-to-r from-red-500 to-rose-600 text-white shadow shadow-red-200',
                                                    'icon' => 'fa-solid fa-crown',
                                                ],
                                                'member' => [
                                                    'label' => 'Member',
                                                    'class' => 'bg-gray-100 text-gray-600 border border-gray-200',
                                                    'icon' => 'fa-solid fa-user',
                                                ],
                                            ];
                                            $role = $roles[$user->role] ?? $roles['member'];
                                        @endphp
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold rounded-full {{ $role['class'] }}">
                                            <i class="{{ $role['icon'] }} text-[11px]"></i>
                                            {{ $role['label'] }}
                                        </span>
                                    </td>

                                    {{-- Job Tittle --}}
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

                                    {{-- CREATED --}}
                                    <td class="px-6 py-4 text-gray-500">
                                        {{ $user->created_at->format('d M Y') }}
                                    </td>

                                    {{-- ACTIONS --}}
                                    <td class="px-6 py-4">
                                        <div class="flex justify-end items-center gap-2">
                                            @if ($user->id === Auth::id())
                                                <span
                                                    class="px-3 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-600">
                                                    You
                                                </span>
                                            @else
                                                {{-- edit --}}
                                                <a href="{{ route('management-users.edit', $user) }}" title="Edit"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                                    <i class="fa-solid fa-pen"></i>
                                                </a>

                                                {{-- delete --}}
                                                <form method="POST"
                                                    action="{{ route('management-users.destroy', $user) }}"
                                                    onsubmit="return confirm('Are you sure you want to delete this user?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" title="Delete"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                                        <i class="fa-regular fa-trash-can"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>

                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-400">
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
                                                <a href="{{ route('management-users.index') }}"
                                                    class="text-indigo-500 hover:underline">Reset search</a>
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
@endsection
