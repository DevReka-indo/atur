@extends('layouts.app')

@section('title', 'Workspace Management')

@section('content')

    @php
        $totalWorkspaces = $workspaces->count();
        $activeWorkspaces = $workspaces->filter(fn($ws) => $ws->projects_count > 0)->count();
        $emptyWorkspaces = $workspaces->filter(fn($ws) => $ws->projects_count === 0)->count();
        $totalMembers = $workspaces->sum('members_count');
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

        {{-- Flash Messages --}}
        @if (session('success'))
            <div
                class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-check text-green-500"></i>
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 flex items-center gap-2">
                <i class="fa-solid fa-circle-xmark text-red-500"></i>
                {{ session('error') }}
            </div>
        @endif

        {{-- Page Header --}}
        <div class="mb-6">
            <div class="flex items-center gap-2">
                <h1 class="text-4xl font-semibold text-slate-900">
                    Management Workspaces
                </h1>

                <button onclick="openManageWorkspaceModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="mt-1 text-sm text-gray-500">Manage and monitor all workspaces in the system.</p>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-2 gap-4 mb-8 lg:grid-cols-4">

            {{-- Total Workspaces --}}
            <div
                class="bg-white rounded-xl border-t-4 border-blue-500 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Total Workspaces</p>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $totalWorkspaces }}</p>
                <p class="text-xs text-emerald-600 font-medium flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 10l7-7m0 0l7 7m-7-7v18" />
                    </svg>
                    Overall
                </p>
            </div>

            {{-- Has Projects --}}
            <div
                class="bg-white rounded-xl border-t-4 border-emerald-500 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Has Projects</p>
                    <div class="p-2 bg-emerald-50 rounded-lg">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $activeWorkspaces }}</p>
                <p class="text-xs text-emerald-600 font-medium">With active projects</p>
            </div>

            {{-- Empty Workspaces --}}
            <div class="bg-white rounded-xl border-t-4 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                style="border-top-color:#8b5cf6;">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Empty</p>
                    <div class="p-2 rounded-lg" style="background-color:#ede9fe;">
                        <svg class="w-5 h-5" style="color:#7c3aed;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $emptyWorkspaces }}</p>
                <p class="text-xs font-medium" style="color:#7c3aed;">No projects yet</p>
            </div>

            {{-- Total Members --}}
            <div class="bg-white rounded-xl border-t-4 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                style="border-top-color:#f59e0b;">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Total Members</p>
                    <div class="p-2 rounded-lg" style="background-color:#fef3c7;">
                        <svg class="w-5 h-5" style="color:#d97706;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $totalMembers }}</p>
                <p class="text-xs text-amber-600 font-medium">Across all workspaces</p>
            </div>

        </div>

        {{-- Toolbar: Search --}}
        <div class="flex items-center justify-between gap-4 mb-6">
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="search-input" placeholder="Search workspace name..."
                    class="pl-9 pr-4 py-2.5 w-72 rounded-xl border border-gray-200 bg-white text-sm
                           focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition-all duration-200" />
            </div>
        </div>

        {{-- Workspaces Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-[#ADE8F4]">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">No
                            </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Workspace Name</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Owner</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Members</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Projects</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Created At</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        @forelse ($workspaces as $index => $workspace)
                            <tr class="hover:bg-gray-50 transition-colors workspace-row"
                                data-name="{{ strtolower($workspace->name) }}">

                                {{-- No --}}
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>

                                {{-- Workspace Name --}}
                                <td class="px-5 py-4">
                                    <a href="{{ route('workspaces.show', $workspace->token) }}"
                                        class="flex items-center gap-2 text-sm font-semibold text-gray-900 hover:text-indigo-600 transition">
                                        <div
                                            class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                            <i class="fa-solid fa-layer-group text-indigo-600 text-xs"></i>
                                        </div>
                                        {{ $workspace->name }}
                                    </a>
                                    @if ($workspace->description)
                                        <p class="text-xs text-gray-400 mt-1 ml-10 truncate max-w-xs">
                                            {{ $workspace->description }}</p>
                                    @endif
                                </td>

                                {{-- Owner --}}
                                <td class="px-5 py-4">
                                    @if ($workspace->creator)
                                        <div class="flex items-center gap-2">
                                            @if ($workspace->creator->profile_photo)
                                                <img src="{{ asset('storage/' . $workspace->creator->profile_photo) }}"
                                                    class="w-7 h-7 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                                            @else
                                                <div
                                                    class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-indigo-600">
                                                        {{ strtoupper(substr($workspace->creator->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <span class="text-sm text-gray-700">{{ $workspace->creator->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Members --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        {{-- Avatar stack (max 3) --}}
                                        <div class="flex -space-x-2">
                                            @foreach ($workspace->members->take(3) as $member)
                                                @if ($member->profile_photo)
                                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                        class="w-6 h-6 rounded-full border-2 border-white object-cover"
                                                        title="{{ $member->name }}">
                                                @else
                                                    <div class="w-6 h-6 rounded-full border-2 border-white bg-gray-300 flex items-center justify-center"
                                                        title="{{ $member->name }}">
                                                        <span class="text-[9px] font-bold text-gray-600">
                                                            {{ strtoupper(substr($member->name, 0, 1)) }}
                                                        </span>
                                                    </div>
                                                @endif
                                            @endforeach
                                            @if ($workspace->members_count > 3)
                                                <div
                                                    class="w-6 h-6 rounded-full border-2 border-white bg-gray-200 flex items-center justify-center">
                                                    <span
                                                        class="text-[9px] font-bold text-gray-500">+{{ $workspace->members_count - 3 }}</span>
                                                </div>
                                            @endif
                                        </div>
                                        <span class="text-sm text-gray-600">{{ $workspace->members_count }}</span>
                                    </div>
                                </td>

                                {{-- Projects --}}
                                <td class="px-5 py-4">
                                    <div class="flex items-center gap-1.5">
                                        @if ($workspace->projects_count > 0)
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <i class="fa-solid fa-diagram-project text-[10px]"></i>
                                                {{ $workspace->projects_count }} projects
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-gray-100 text-gray-500">
                                                <i class="fa-regular fa-folder-open text-[10px]"></i>
                                                No projects
                                            </span>
                                        @endif
                                    </div>
                                </td>

                                {{-- Created At --}}
                                <td class="px-5 py-4">
                                    <span
                                        class="text-sm text-gray-600">{{ $workspace->created_at->format('d M Y') }}</span>
                                    <span
                                        class="block text-xs text-gray-400">{{ $workspace->created_at->diffForHumans() }}</span>
                                </td>

                                {{-- Actions --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('workspaces.show', $workspace->token) }}" title="View"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                            <i class="fa-regular fa-eye"></i>
                                        </a>
                                        <button onclick="deleteWorkspace('{{ $workspace->token }}')" title="Delete"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div
                                            class="w-16 h-16 mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fa-solid fa-layer-group text-2xl text-gray-400"></i>
                                        </div>
                                        <p class="text-sm text-gray-500 font-medium">No workspaces found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Empty search state --}}
                        <tr id="empty-search" style="display:none;">
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fa-solid fa-layer-group text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500 font-medium">No workspaces found for "<span
                                            id="search-keyword"></span>"</p>
                                    <button onclick="document.getElementById('search-input').value=''; filterWorkspaces();"
                                        class="mt-2 text-xs text-indigo-500 hover:underline">Reset search</button>
                                </div>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <script>
        const searchInput = document.getElementById('search-input');
        searchInput.addEventListener('input', filterWorkspaces);

        function filterWorkspaces() {
            const keyword = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.workspace-row');
            let visibleCount = 0;

            rows.forEach(row => {
                const name = row.dataset.name;
                if (name.includes(keyword)) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            const emptySearch = document.getElementById('empty-search');
            if (visibleCount === 0 && keyword !== '') {
                document.getElementById('search-keyword').textContent = searchInput.value;
                emptySearch.style.display = '';
            } else {
                emptySearch.style.display = 'none';
            }
        }

        function deleteWorkspace(token) {
            if (confirm('Are you sure you want to delete this workspace? All projects inside will also be deleted.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/management-workspaces/${token}`;
                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${csrfToken}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>




    <!-- POP UPNYA JOSJIS  -->
    <div id="manageWorkspaceModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">
                Tentang Management Workspaces
            </h2>

            <div class="space-y-5 text-sm text-slate-600">

                <p class="leading-relaxed">
                    Management Workspaces digunakan untuk mengelola seluruh workspace dalam sistem,
                    termasuk pengaturan struktur kerja, anggota, serta hubungan antar project dalam satu.
                </p>

                <div class="border-t pt-4 space-y-4">

                    <div class="flex items-start gap-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Pengelolaan workspace</span><br>
                            <span class="text-slate-500">Membuat, mengedit, dan menghapus workspace sesuai kebutuhan tim
                                atau.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Pengaturan anggota</span><br>
                            <span class="text-slate-500">Menambahkan dan mengatur anggota yang tergabung dalam
                                workspace.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Struktur project</span><br>
                            <span class="text-slate-500">Mengelompokkan project agar lebih terorganisir dalam satu
                                workspace.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Kontrol akses</span><br>
                            <span class="text-slate-500">Mengatur hak akses setiap anggota untuk menjaga keamanan dan
                                keteraturan data.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Efisiensi.</span><br>
                            <span class="text-slate-500">Memudahkan pengelolaan banyak tim dan project dalam satu sistem
                                terpusat.</span>
                        </p>
                    </div>

                </div>
            </div>

            <!-- BUTTON -->
            <div class="flex justify-end gap-2 mt-6">
                <button onclick="confirmManageWorkspace()"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Selesai
                </button>
            </div>

        </div>
    </div>

    <!-- SCRIPT  -->
    <script>
        window.openManageWorkspaceModal = function() {
            const modal = document.getElementById('manageWorkspaceModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        window.closeManageWorkspaceModal = function() {
            const modal = document.getElementById('manageWorkspaceModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        window.confirmManageWorkspace = function() {
            closeManageWorkspaceModal();
            console.log("User lanjut management workspace");
        }
    </script>
@endsection
