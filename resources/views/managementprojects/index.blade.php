@extends('layouts.app')

@section('title', 'Project Management')

@section('content')

    @php
        $totalProjects = $projects->count();
        $activeProjects = $projects->where('status', 'active')->count();
        $completedProjects = $projects->where('status', 'completed')->count();
        $onHoldProjects = $projects->where('status', 'on_hold')->count();
        $urgentProjects = $projects->where('status', 'urgent')->count();
    @endphp

    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

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
                    Management Projects
                </h1>

                <button onclick="openManageProjectModal()"
                    class="w-6 h-6 flex items-center justify-center rounded-full text-slate-400 hover:text-blue-500 transition">
                    <i class="fa-solid fa-circle-info"></i>
                </button>
            </div>
            <p class="mt-1 text-sm text-gray-500">Manage and monitor all projects in the system.</p>
        </div>

        {{-- Stat Cards --}}
        <div class="grid grid-cols-2 gap-4 mb-8 lg:grid-cols-4">

            {{-- Total Projects --}}
            <div
                class="bg-white rounded-xl border-t-4 border-blue-500 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Total Projects</p>
                    <div class="p-2 bg-blue-50 rounded-lg">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2 2z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $totalProjects }}</p>
                <p class="text-xs text-emerald-600 font-medium flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18">
                        </path>
                    </svg>
                    Overall
                </p>
            </div>

            {{-- Active --}}
            <div
                class="bg-white rounded-xl border-t-4 border-emerald-500 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Active</p>
                    <div class="p-2 bg-emerald-50 rounded-lg">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $activeProjects }}</p>
                <p class="text-xs text-emerald-600 font-medium">In progress</p>
            </div>

            {{-- Completed --}}
            <div class="bg-white rounded-xl border-t-4 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                style="border-top-color:#8b5cf6;">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">Completed</p>
                    <div class="p-2 rounded-lg" style="background-color:#ede9fe;">
                        <svg class="w-5 h-5" style="color:#7c3aed;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4">
                            </path>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $completedProjects }}</p>
                <p class="text-xs text-blue-600 font-medium">This quarter</p>
            </div>

            {{-- On Hold --}}
            <div class="bg-white rounded-xl border-t-4 shadow-sm px-5 py-5 hover:shadow-lg transition-all duration-300 hover:-translate-y-1"
                style="border-top-color:#f59e0b;">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-xs font-semibold uppercase tracking-widest text-gray-500">On Hold</p>
                    <div class="p-2 rounded-lg" style="background-color:#fef3c7;">
                        <svg class="w-5 h-5" style="color:#d97706;" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
                <p class="text-4xl font-extrabold text-gray-900 leading-none mb-2">{{ $onHoldProjects }}</p>
                <p class="text-xs text-amber-600 font-medium">Needs attention</p>
            </div>

        </div>

        {{-- Toolbar: Search + Add Project --}}
        <div class="flex items-center justify-between gap-4 mb-6">

            {{-- Search (client-side, no controller change needed) --}}
            <div class="relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                <input type="text" id="search-input" placeholder="Search project name..."
                    class="pl-9 pr-4 py-2.5 w-72 rounded-xl border border-gray-200 bg-white text-sm
                        focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 transition-all duration-200" />
            </div>

        </div>

        {{-- Projects Table --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden mt-4">
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-[#ADE8F4]">
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                No </th>
                            <th class="px-6 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Project Name</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Creator</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Workspace</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Status</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Members</th>
                            <th class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Tasks</th>
                            <th class="px-6 py-4 text-center text-xs font-semibold uppercase tracking-wider text-gray-600">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">

                        @forelse ($projects as $index => $project)
                            @php
                                $statusBadge = match ($project->status) {
                                    'planning' => 'bg-gray-100 text-gray-700',
                                    'active' => 'bg-emerald-100 text-emerald-800',
                                    'on_hold' => 'bg-amber-100 text-amber-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'cancelled' => 'bg-red-100 text-red-700',
                                    'urgent' => 'bg-orange-200 text-orange-800',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <tr class="hover:bg-gray-50 transition-colors project-row"
                                data-name="{{ strtolower($project->name) }}">

                                {{-- No --}}
                                <td class="px-6 py-4 text-sm text-gray-500">{{ $index + 1 }}</td>

                                {{-- Project Name --}}
                                <td class="px-5 py-4">
                                    <a href="{{ route('projects.show', $project->token) }}"
                                        class="group flex items-center gap-2 text-sm font-semibold text-gray-900 hover:text-indigo-600 transition">

                                        <div
                                            class="w-8 h-8 rounded-lg bg-blue-100 group-hover:bg-blue-200 transition flex items-center justify-center flex-shrink-0">
                                            <i
                                                class="fa-solid fa-diagram-project text-blue-600 group-hover:text-blue-700 text-xs"></i>
                                        </div>

                                        <span>{{ $project->name }}</span>
                                    </a>
                                </td>

                                {{-- Owner --}}
                                <td class="px-5 py-4">
                                    @if ($project->creator)
                                        <div class="flex items-center gap-2">
                                            @if ($project->creator->profile_photo)
                                                <img src="{{ asset('storage/' . $project->creator->profile_photo) }}"
                                                    class="w-7 h-7 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                                            @else
                                                <div
                                                    class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                    <span class="text-xs font-bold text-indigo-600">
                                                        {{ strtoupper(substr($project->creator->name, 0, 1)) }}
                                                    </span>
                                                </div>
                                            @endif
                                            <span class="text-sm text-gray-700">{{ $project->creator->name }}</span>
                                        </div>
                                    @else
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Workspace --}}
                                <td class="px-5 py-4">
                                    @if ($project->workspace)
                                        <a href="{{ route('workspaces.show', $project->workspace->token) }}"
                                            class="inline-flex items-center gap-2 text-sm text-gray-500 hover:text-indigo-600 hover:underline transition-colors">
                                            <i class="fa-solid fa-layer-group w-5 text-center text-sm"></i>
                                            {{ $project->workspace->name }}
                                        </a>
                                    @else
                                        <span class="text-sm text-gray-400">—</span>
                                    @endif
                                </td>

                                {{-- Status Badge --}}
                                <td class="px-5 py-4">
                                    <span
                                        class="inline-flex items-center gap-1 px-3 py-1 rounded-md text-xs font-medium cursor-pointer hover:opacity-80 transition-opacity w-full justify-between {{ $statusBadge }}">
                                        {{ ucfirst(str_replace('_', ' ', $project->status)) }}
                                    </span>
                                </td>

                                {{-- Members --}}
                                <td class="px-5 py-4">
                                    <span class="text-sm text-gray-700">{{ $project->members->count() }}</span>
                                </td>

                                {{-- Tasks --}}
                                <td class="px-5 py-4">
                                    <span
                                        class="block text-sm font-medium text-gray-800">{{ $project->tasks_count ?? 0 }}</span>
                                    <span class="block text-xs text-gray-400">tasks</span>
                                </td>

                                {{-- Actions: Edit + Delete only --}}
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-1">
                                        <a href="{{ route('projects.show', $project->token) }}" title="View"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <a href="{{ route('projects.edit', $project->token) }}" title="Edit"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <button onclick="deleteProject('{{ $project->token }}')" title="Delete"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                            <i class="fa-regular fa-trash-can"></i>
                                        </button>
                                    </div>
                                </td>

                            </tr>

                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div
                                            class="w-16 h-16 mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                                            <i class="fa-regular fa-folder-open text-2xl text-gray-400"></i>
                                        </div>
                                        <p class="text-sm text-gray-500 font-medium">No projects found</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse

                        {{-- Empty state saat search tidak menemukan hasil --}}
                        <tr id="empty-search" style="display:none;">
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <div class="w-16 h-16 mb-4 rounded-full bg-gray-100 flex items-center justify-center">
                                        <i class="fa-regular fa-folder-open text-2xl text-gray-400"></i>
                                    </div>
                                    <p class="text-sm text-gray-500 font-medium">No projects found for "<span
                                            id="search-keyword"></span>"</p>
                                    <button onclick="document.getElementById('search-input').value=''; filterProjects();"
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
        searchInput.addEventListener('input', filterProjects);

        function filterProjects() {
            const keyword = searchInput.value.toLowerCase().trim();
            const rows = document.querySelectorAll('.project-row');
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

        // Delete
        function deleteProject(id) {
            if (confirm('Are you sure you want to delete this project?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/projects/${id}?redirect=management`;
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

    <!-- POP UPNYA YA LEK KUU -->
    <div div id="manageProjectModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50 p-4">

        <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[80vh] overflow-y-auto p-6 animate-fadeIn">

            <h2 class="text-xl font-semibold mb-3">
                Tentang Management Projects
            </h2>

            <div class="space-y-5 text-sm text-slate-600">

                <p class="leading-relaxed">
                    Management Projects digunakan untuk mengelola seluruh project dalam sistem,
                    termasuk pengaturan data, monitoring aktivitas, serta pengendalian akses pengguna.
                </p>

                <div class="border-t pt-4 space-y-4">

                    <div class="flex items-start gap-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Pengelolaan data project</span><br>
                            <span class="text-slate-500">Menambah, mengedit, dan menghapus project sesuai kebutuhan.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Monitoring aktivitas</span><br>
                            <span class="text-slate-500">Memantau perkembangan dan aktivitas yang terjadi di setiap
                                project.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Manajemen anggota</span><br>
                            <span class="text-slate-500">Mengatur siapa saja yang dapat mengakses dan berkontribusi dalam
                                project.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Kontrol akses</span><br>
                            <span class="text-slate-500">Menentukan hak akses pengguna agar keamanan data tetap
                                terjaga.</span>
                        </p>
                    </div>

                    <div class="flex items-start gap-3 border-t pt-3">
                        <span class="text-slate-800 mt-1">●</span>
                        <p>
                            <span class="font-semibold text-slate-800">Efisiensi pengelolaan</span><br>
                            <span class="text-slate-500">Semua project dapat dikelola dalam satu halaman secara
                                terpusat.</span>
                        </p>
                    </div>

                </div>
            </div>

            <!-- BUTTON -->
            <div class="flex justify-end gap-2 mt-6">
                <button onclick="confirmManageProject()"
                    class="px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">
                    Selesai
                </button>
            </div>

        </div>
    </div>

    <script>
        window.openManageProjectModal = function() {
            const modal = document.getElementById('manageProjectModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        window.closeManageProjectModal = function() {
            const modal = document.getElementById('manageProjectModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        window.confirmManageProject = function() {
            closeManageProjectModal();
            console.log("User lanjut management project");
        }
    </script>
@endsection
