<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ATUR')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/Logo Badge.svg') }}">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            scroll-behavior: smooth;
        }

        #sidebar {
            transition: transform 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.35s ease;
        }

        #main-content {
            transition: margin-left 0.35s cubic-bezier(0.4, 0, 0.2, 1);
        }

        #sidebar-overlay {
            transition: opacity 0.35s ease;
        }

        #sidebar-overlay.show {
            opacity: 1;
            pointer-events: auto;
        }

        #sidebar-overlay.hide {
            opacity: 0;
            pointer-events: none;
        }

        @media (min-width: 768px) {
            #sidebar.sidebar-hidden {
                margin-left: -16rem;
                opacity: 0.8;
            }

            #main-content.sidebar-hidden {
                margin-left: 0;
            }
        }

        @media (max-width: 767px) {
            #sidebar {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
            }

            #sidebar.sidebar-hidden {
                transform: translateX(-100%);
            }
        }

        .dropdown-animate {
            animation: fadeDropdown 0.18s ease-out;
        }

        @keyframes fadeDropdown {
            from {
                opacity: 0;
                transform: translateY(-6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes popUp {
            from {
                opacity: 0;
                transform: scale(0.92) translateY(12px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gray- font-sans text-gray-800">
    <div class="flex h-screen relative">

        <!-- SIDEBAR -->
        <aside id="sidebar"
            class="w-64 bg-[#d6cfbf] border-r border-gray-300 relative z-50 shadow-sm h-full flex flex-col overflow-hidden flex-shrink-0">
            @include('layouts.sidebar')
        </aside>

        <div id="sidebar-overlay" class="fixed inset-0 bg-black/40 hidden z-40 md:hidden"></div>

        <div id="main-content" class="flex-1 flex flex-col md:ml-0">

            <!-- TOPBAR -->
            <header class="border-b border-gray-200 bg-white sticky top-0 z-30 backdrop-blur-sm bg-white/90">
                <div class="flex justify-between items-center px-6 h-14">

                    <!-- LEFT -->
                    <div class="flex items-center gap-4">
                        <button id="hamburger" class="text-lg hover:scale-110 transition">
                            <i class="fas fa-bars"></i>
                        </button>
                    </div>

                    <!-- RIGHT -->
                    <div class="flex items-center gap-5 relative">

                        <!-- GLOBAL LIVE SEARCH -->
                        <div class="relative">
                            <input type="text" id="global-search" placeholder="Search projects or tasks..."
                                class="w-64 pl-10 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-cyan-500 focus:outline-none text-sm"
                                autocomplete="off">

                            <div id="search-results"
                                class="absolute left-0 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-xl hidden z-50 max-h-80 overflow-y-auto">
                            </div>
                        </div>

                        @php
                            $unreadCount = \App\Models\Notification::where('user_id', auth()->id())
                                ->whereNull('read_at')
                                ->count();
                        @endphp

                        <a href="{{ route('notifications.index') }}" style="position:relative;">
                            <i class="fas fa-bell"></i>
                            <span id="notif-badge"
                                style="position:absolute; top:-5px; right:-8px; background:red; color:white; border-radius:50%; padding:2px 6px; font-size:11px; {{ $unreadCount > 0 ? '' : 'display:none;' }}">
                                {{ $unreadCount > 0 ? $unreadCount : '' }}
                            </span>
                        </a>

                        <!-- SETTINGS -->
                        <div class="relative">
                            <button id="settings-btn" <i class="fa-solid fa-gear"></i>
                            </button>

                            <div id="settings-menu"
                                class="absolute right-0 mt-3 w-52 bg-white border border-gray-200
                                    rounded-xl shadow-xl hidden z-50 overflow-hidden">

                                <a href="{{ route('settings.account') }}"
                                    class="block px-5 py-3 text-sm hover:bg-gray-100">
                                    <i class="fas fa-user-cog mr-2"></i> Account Settings
                                </a>

                                <a href="{{ route('settings.about') }}"
                                    class="block px-5 py-3 text-sm hover:bg-gray-100">
                                    <i class="fas fa-info-circle mr-2"></i> About System
                                </a>
                            </div>
                        </div>

                        <!-- PROFILE -->
                        @auth
                            <div class="relative">
                                <button id="profile-btn" type="button" class="relative">
                                    @if (!Auth::user()->has_password)
                                        <span
                                            style="position:absolute; top:-4px; right:-4px; width:12px; height:12px; background:#f59e0b; border:2px solid white; border-radius:50%; z-index:10;"></span>
                                    @endif
                                    @if (Auth::user()->profile_photo)
                                        <img src="{{ asset('storage/' . Auth::user()->profile_photo) }}"
                                            class="w-9 h-9 rounded-full border border-gray-300 object-cover shadow-sm">
                                    @else
                                        <div
                                            class="w-9 h-9 rounded-full border border-gray-300 bg-gray-200 flex items-center justify-center shadow-sm">
                                            <span class="text-xs font-semibold">
                                                {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                            </span>
                                        </div>
                                    @endif
                                </button>

                                <div id="profile-menu"
                                    class="absolute right-0 mt-3 w-44 bg-white border border-gray-200
                                    rounded-xl shadow-xl hidden z-50 overflow-hidden">

                                    <a href="{{ route('profile.edit') }}"
                                        class="block px-5 py-3 text-sm hover:bg-gray-100 flex items-center justify-between">
                                        <span><i class="fas fa-user mr-2"></i> Profile</span>
                                        @if (!Auth::user()->has_password)
                                            <span class="w-2 h-2 bg-amber-400 rounded-full"></span>
                                        @endif
                                    </a>

                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit"
                                            class="w-full text-left px-5 py-3 text-sm text-red-600 hover:bg-gray-100">
                                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth

                    </div>
                </div>
            </header>

            <main class="p-8 flex-1 overflow-y-auto" id="main-scroll">
                @yield('content')
            </main>

        </div>
    </div>
    {{-- Pop Up Akses Ditolak (Compact & Elegant) --}}
    @if (session('access_denied'))
        <div id="accessDeniedPopup"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/10 backdrop-blur-sm p-4 transition-opacity duration-300 ease-out">

            {{-- Kotak Popup Kecil --}}
            <div
                class="bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] w-72 p-6 text-center ring-1 ring-gray-100 relative transform transition-all animate-[fadeIn_0.3s_ease-out]">

                {{-- Icon Lock yang lebih proporsional --}}
                <div
                    class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4 border border-red-100">
                    <i class="fa-solid fa-lock text-red-500 text-lg"></i>
                </div>

                {{-- Judul & Pesan --}}
                <h2 class="text-base font-bold text-gray-800 mb-1">Akses Ditolak</h2>
                <p class="text-xs text-gray-500 mb-5 leading-relaxed px-2">
                    {{ session('access_denied') }}
                </p>

                {{-- Tombol Mengerti (Lebih compact) --}}
                <button onclick="document.getElementById('accessDeniedPopup').remove()"
                    class="w-full py-2 rounded-lg bg-red-500 hover:bg-red-600 text-white text-sm font-medium transition-colors shadow-md shadow-red-500/20 active:scale-95">
                    Mengerti
                </button>

            </div>
        </div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {

            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const hamburger = document.getElementById('hamburger');
            const mainContent = document.getElementById('main-content');

            if (localStorage.getItem('sidebarHidden') === 'true') {
                sidebar?.classList.add('sidebar-hidden');
                mainContent?.classList.add('sidebar-hidden');
            }

            function openSidebar() {
                sidebar?.classList.remove('sidebar-hidden');
                mainContent?.classList.remove('sidebar-hidden');
                overlay?.classList.remove('hidden');
                setTimeout(() => overlay?.classList.add('show'), 10);
                overlay?.classList.remove('hide');
                localStorage.setItem('sidebarHidden', 'false');
            }

            function closeSidebar() {
                sidebar?.classList.add('sidebar-hidden');
                mainContent?.classList.add('sidebar-hidden');
                overlay?.classList.add('hide');
                overlay?.classList.remove('show');
                setTimeout(() => overlay?.classList.add('hidden'), 350);
                localStorage.setItem('sidebarHidden', 'true');
            }

            hamburger?.addEventListener('click', function(e) {
                e.stopPropagation();
                if (sidebar?.classList.contains('sidebar-hidden')) {
                    openSidebar();
                } else {
                    closeSidebar();
                }
            });

            overlay?.addEventListener('click', function() {
                closeSidebar();
            });

            const settingsBtn = document.getElementById('settings-btn');
            const settingsMenu = document.getElementById('settings-menu');
            const profileBtn = document.getElementById('profile-btn');
            const profileMenu = document.getElementById('profile-menu');

            settingsBtn?.addEventListener('click', function(e) {
                e.stopPropagation();
                settingsMenu?.classList.toggle('hidden');
                profileMenu?.classList.add('hidden');
            });

            profileBtn?.addEventListener('click', function(e) {
                e.stopPropagation();
                profileMenu?.classList.toggle('hidden');
                settingsMenu?.classList.add('hidden');
            });

            document.addEventListener('click', function() {
                settingsMenu?.classList.add('hidden');
                profileMenu?.classList.add('hidden');
            });

            // LIVE SEARCH
            const searchInput = document.getElementById("global-search");
            const resultsBox = document.getElementById("search-results");

            const HISTORY_KEY = 'search_history';
            const MAX_HISTORY = 5;

            function getHistory() {
                return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
            }

            function saveHistory(keyword) {
                let history = getHistory().filter(h => h !== keyword);
                history.unshift(keyword);
                if (history.length > MAX_HISTORY) history = history.slice(0, MAX_HISTORY);
                localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
            }

            function deleteHistory(keyword) {
                let history = getHistory().filter(h => h !== keyword);
                localStorage.setItem(HISTORY_KEY, JSON.stringify(history));
            }

            function clearAllHistory() {
                localStorage.removeItem(HISTORY_KEY);
            }

            function renderHistory() {
                const history = getHistory();
                if (!history.length) {
                    resultsBox.classList.add('hidden');
                    return;
                }

                resultsBox.innerHTML = `
        <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
            <span class="text-xs text-gray-400 uppercase font-semibold">Recent Searches</span>
            <button id="clear-all-history" class="text-xs text-red-400 hover:text-red-600 transition">Clear All</button>
        </div>
    `;

                history.forEach(keyword => {
                    resultsBox.innerHTML += `
            <div class="flex items-center justify-between px-4 py-2 hover:bg-gray-50 group">
                <button class="history-item flex items-center gap-2 text-sm text-gray-600 flex-1 text-left" data-keyword="${keyword}">
                    <i class="fa-solid fa-clock-rotate-left text-xs text-gray-300"></i>
                    ${keyword}
                </button>
                <button class="delete-history text-xs text-gray-300 hover:text-red-400 transition opacity-0 group-hover:opacity-100" data-keyword="${keyword}">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        `;
                });

                resultsBox.classList.remove('hidden');

                // Event clear all
                document.getElementById('clear-all-history')?.addEventListener('click', function(e) {
                    e.stopPropagation();
                    clearAllHistory();
                    resultsBox.classList.add('hidden');
                });

                // Event klik history item
                document.querySelectorAll('.history-item').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        searchInput.value = this.dataset.keyword;
                        doSearch(this.dataset.keyword);
                    });
                });

                // Event hapus per item
                document.querySelectorAll('.delete-history').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.stopPropagation();
                        deleteHistory(this.dataset.keyword);
                        renderHistory();
                    });
                });
            }

            function renderResults(data, query) {
                resultsBox.innerHTML = '';

                if (!data.workspaces.length && !data.projects.length && !data.tasks.length) {
                    resultsBox.innerHTML = '<div class="p-4 text-sm text-gray-400">No results found</div>';
                    resultsBox.classList.remove('hidden');
                    return;
                }

                if (data.workspaces.length) {
                    resultsBox.innerHTML +=
                        '<div class="px-4 py-2 text-xs text-gray-400 uppercase">Workspaces</div>';
                    data.workspaces.forEach(workspace => {
                        resultsBox.innerHTML += `
                <a href="/workspaces/${workspace.token}" class="search-result-link block px-4 py-2 text-sm hover:bg-emerald-50" data-keyword="${query}">
                    <i class="fa-solid fa-layer-group text-xs text-gray-300 mr-2"></i>${workspace.name}
                </a>`;
                    });
                }

                if (data.projects.length) {
                    resultsBox.innerHTML += '<div class="px-4 py-2 text-xs text-gray-400 uppercase">Projects</div>';
                    data.projects.forEach(project => {
                        resultsBox.innerHTML += `
                <a href="/projects/${project.token}" class="search-result-link block px-4 py-2 text-sm hover:bg-cyan-50" data-keyword="${query}">
                    <i class="fa-solid fa-diagram-project text-xs text-gray-300 mr-2"></i>${project.name}
                </a>`;
                    });
                }

                if (data.tasks.length) {
                    resultsBox.innerHTML += '<div class="px-4 py-2 text-xs text-gray-400 uppercase">Tasks</div>';
                    data.tasks.forEach(task => {
                        resultsBox.innerHTML += `
                <a href="/tasks/${task.token}" class="search-result-link block px-4 py-2 text-sm hover:bg-blue-50" data-keyword="${query}">
                    <i class="fa-solid fa-list-check text-xs text-gray-300 mr-2"></i>${task.name}
                </a>`;
                    });
                }

                resultsBox.classList.remove('hidden');

                // Simpan ke history saat klik hasil
                document.querySelectorAll('.search-result-link').forEach(link => {
                    link.addEventListener('click', function() {
                        saveHistory(this.dataset.keyword);
                    });
                });
            }

            function doSearch(query) {
                fetch("{{ route('live.search') }}?q=" + encodeURIComponent(query))
                    .then(response => response.json())
                    .then(data => renderResults(data, query))
                    .catch(error => console.log(error));
            }

            if (searchInput) {
                let debounceTimer;

                // Tampilkan history saat fokus jika input kosong
                searchInput.addEventListener('focus', function() {
                    if (!this.value.trim()) {
                        renderHistory();
                    }
                });

                searchInput.addEventListener('keyup', function() {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();

                    if (query.length < 2) {
                        if (query.length === 0) renderHistory();
                        else resultsBox.classList.add('hidden');
                        return;
                    }

                    debounceTimer = setTimeout(() => doSearch(query), 300);
                });

                // Simpan history saat tekan Enter
                searchInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Enter' && this.value.trim().length >= 2) {
                        saveHistory(this.value.trim());
                    }
                });

                document.addEventListener('click', function(e) {
                    if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
                        resultsBox.classList.add('hidden');
                    }
                });
            }

        });
    </script>

    @stack('scripts')


    {{-- INVITATION BANNER --}}
    @if (session('invitation_token'))
        @php
            $invToken = session('invitation_token');
            $invData = \App\Models\Invitation::findByPlainTextToken($invToken);
            $invData = $invData?->isUsable() ? $invData->load('inviter') : null;
            $invitable = $invData
                ? ($invData->type === 'workspace'
                    ? \App\Models\Workspace::find($invData->invitable_id)
                    : \App\Models\Project::find($invData->invitable_id))
                : null;
        @endphp

        @if ($invData && $invitable)
            <div id="inv-banner"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/30 backdrop-blur-sm"
                style="animation: fadeIn 0.3s ease both">

                <div class="bg-white rounded-2xl shadow-2xl w-80 overflow-hidden"
                    style="animation: popUp 0.35s cubic-bezier(0.16,1,0.3,1) both">

                    {{-- Top accent bar --}}
                    <div
                        class="h-1 w-full {{ $invData->type === 'workspace' ? 'bg-gradient-to-r from-violet-400 to-purple-500' : 'bg-gradient-to-r from-blue-400 to-indigo-500' }}">
                    </div>

                    <div class="px-6 py-5">

                        {{-- Icon + Badge --}}
                        <div class="flex items-center justify-between mb-4">
                            <div
                                class="w-11 h-11 rounded-xl flex items-center justify-center {{ $invData->type === 'workspace' ? 'bg-violet-100' : 'bg-indigo-100' }}">
                                <i
                                    class="fa-solid {{ $invData->type === 'workspace' ? 'fa-folder-tree text-violet-600' : 'fa-diagram-project text-indigo-600' }}"></i>
                            </div>
                            <span
                                class="text-xs font-semibold px-2.5 py-1 rounded-full {{ $invData->type === 'workspace' ? 'bg-violet-100 text-violet-700' : 'bg-indigo-100 text-indigo-700' }}">
                                {{ ucfirst($invData->type) }}
                            </span>
                        </div>

                        {{-- Title --}}
                        <h3 class="text-base font-bold text-gray-900">You're invited to join</h3>
                        <p class="text-lg font-extrabold text-gray-900 mt-0.5">"{{ $invitable->name }}"</p>

                        {{-- Inviter info --}}
                        <div class="mt-3 flex items-center gap-2.5">
                            <div
                                class="w-7 h-7 rounded-full bg-gray-200 flex items-center justify-center text-xs font-bold text-gray-600 uppercase">
                                {{ substr($invData->inviter->name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Invited by <span
                                        class="font-semibold text-gray-800">{{ $invData->inviter->name }}</span></p>
                                <p class="text-xs text-gray-400">{{ $invData->inviter->email }}</p>
                            </div>
                        </div>

                        {{-- Expiry --}}
                        <div
                            class="mt-3 flex items-center gap-1.5 text-xs text-amber-600 bg-amber-50 border border-amber-100 px-3 py-1.5 rounded-lg">
                            <i class="fa-regular fa-clock text-xs"></i>
                            Expires {{ $invData->expires_at->format('d M Y, H:i') }}
                        </div>

                        {{-- Actions --}}
                        <div class="mt-4 flex gap-2.5">
                            <form action="{{ route('invitations.join') }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-xl transition">
                                    Accept
                                </button>
                            </form>
                            <form action="{{ route('invitations.reject') }}" method="POST" class="flex-1">
                                @csrf
                                <button type="submit"
                                    class="w-full py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-semibold rounded-xl transition">
                                    Reject
                                </button>
                            </form>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="px-6 py-3 bg-gray-50 border-t border-gray-100 text-center">
                        <p class="text-xs text-gray-400">
                            Contact
                            <a href="mailto:{{ $invData->inviter->email }}"
                                class="text-gray-600 font-medium hover:underline">
                                {{ $invData->inviter->email }}
                            </a>
                            for any queries.
                        </p>
                    </div>

                </div>
            </div>
        @endif
    @endif

    {{-- Modal Invite Users --}}
    <div id="inviteModal"
        class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6">

            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-lg font-bold text-gray-900">Undang Orang</h2>
                    <p class="text-xs text-gray-400 mt-0.5">Mereka akan menerima undangan email</p>
                </div>
                <button onclick="document.getElementById('inviteModal').classList.add('hidden')"
                    class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-gray-100 text-gray-500 transition">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            @if (session('invite_success'))
                <div
                    class="mb-4 px-4 py-2.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">
                    <i class="fa-solid fa-circle-check mr-1"></i> {{ session('invite_success') }}
                </div>
            @endif
            @if (session('invite_error'))
                <div class="mb-4 px-4 py-2.5 bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl">
                    <i class="fa-solid fa-circle-exclamation mr-1"></i> {{ session('invite_error') }}
                </div>
            @endif

            <form action="{{ route('invitations.send') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Alamat Email</label>
                    <div class="relative">
                        <input type="email" name="email" required placeholder="colleague@example.com"
                            class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-gray-800 focus:border-gray-800 transition">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">Undang Ke</label>
                    <div class="relative">
                        <select name="type" id="inviteType" onchange="updateInvitableOptions()"
                            class="appearance-none w-full px-4 py-2.5 pr-9 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-gray-800 focus:border-gray-800">
                            <option value="workspace">Workspace</option>
                            <option value="project">Project</option>
                        </select>
                        <i
                            class="fa-solid fa-angle-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1.5">
                        Select <span id="inviteTypeLabel">Workspace</span>
                    </label>
                    <div class="relative">
                        <select name="invitable_id"
                            class="appearance-none w-full px-4 py-2.5 pr-9 rounded-xl border border-gray-300 text-sm focus:ring-2 focus:ring-gray-800 focus:border-gray-800">
                            <optgroup label="Workspaces" id="workspaceOptions">
                                @foreach (auth()->user()->workspaces ?? [] as $ws)
                                    <option value="{{ $ws->id }}">{{ $ws->name }}</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Projects" id="projectOptions" style="display:none">
                                @foreach (auth()->user()->projects ?? [] as $proj)
                                    <option value="{{ $proj->id }}">{{ $proj->name }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                        <i
                            class="fa-solid fa-angle-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 text-xs pointer-events-none"></i>
                    </div>
                </div>

                <button type="submit"
                    class="w-full py-2.5 text-white text-sm font-medium rounded-xl transition flex items-center justify-center gap-2"
                    style="background-color: #111827;">
                    <i class="fa-solid fa-paper-plane text-xs"></i>
                    Kirim Undangan
                </button>
            </form>
        </div>
    </div>

    <script>
        function updateInvitableOptions() {
            const type = document.getElementById('inviteType').value;
            document.getElementById('workspaceOptions').style.display = type === 'workspace' ? '' : 'none';
            document.getElementById('projectOptions').style.display = type === 'project' ? '' : 'none';
            document.getElementById('inviteTypeLabel').textContent = type === 'workspace' ? 'Workspace' : 'Project';
        }

        function pollNotifBadge() {
            fetch('{{ route('notifications.poll') }}', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const badge = document.getElementById('notif-badge');
                    if (!badge) return;
                    if (data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.style.display = '';
                    } else {
                        badge.style.display = 'none';
                    }
                })
                .catch(() => {});
        }
        pollNotifBadge();
        setInterval(pollNotifBadge, 30000)

        document.getElementById('inviteModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });

        @if (session('invite_success') || session('invite_error'))
            document.getElementById('inviteModal').classList.remove('hidden');
        @endif
    </script>

    {{-- OVERLOAD TOAST NOTIFICATION --}}
    <div id="overload-toast-container"
        style="position:fixed;bottom:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px;width:350px;">
    </div>

    <script>
        function showOverloadToasts(notifications) {
            const container = document.getElementById('overload-toast-container');
            if (!container) return;

            notifications.forEach((notif, i) => {
                if (notif.type !== 'member_overload') return;

                const toast = document.createElement('div');
                toast.style.cssText =
                    'display:flex; align-items:flex-start; gap:12px; background:white; border:1px solid #fed7aa; box-shadow:0 10px 25px rgba(0,0,0,0.1); border-radius:12px; padding:12px 16px; transition:all 0.5s ease; opacity:0; transform:translateY(-10px); pointer-events:auto;';
                toast.innerHTML = `
                <div style="width:32px;height:32px;border-radius:50%;background:#ffedd5;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                    <i class="fa-solid fa-user-clock" style="color:#f97316;font-size:12px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:12px;font-weight:700;color:#1f2937;margin:0;">${notif.title}</p>
                    <p style="font-size:12px;color:#6b7280;margin:4px 0 0;">${notif.message}</p>
                    <p style="font-size:10px;color:#9ca3af;margin:4px 0 0;">${notif.time}</p>
                </div>
                <button onclick="this.closest('div[style]').remove()" style="color:#d1d5db;background:none;border:none;cursor:pointer;flex-shrink:0;margin-top:2px;font-size:14px;">✕</button>
            `;

                container.appendChild(toast);

                setTimeout(() => {
                    toast.style.opacity = '1';
                    toast.style.transform = 'translateY(0)';
                }, i * 200);

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-10px)';
                    setTimeout(() => toast.remove(), 400);
                }, 7000 + (i * 200));
            });
        }

        function dismissToast(btn) {
            const toast = btn.closest('.pointer-events-auto');
            if (!toast) return;
            toast.classList.add('opacity-0', '-translate-y-2');
            setTimeout(() => toast.remove(), 400);
        }

        function pollOverloadToast() {
            fetch('{{ route('notifications.poll') }}', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const overloadNotifs = (data.notifications || []).filter(n => n.type === 'member_overload');
                    if (overloadNotifs.length > 0) {
                        showOverloadToasts(overloadNotifs);
                    }
                })
                .catch(() => {});
        }

        @if (request()->routeIs('overload.*'))
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(pollOverloadToast, 800);
            });
        @endif
    </script>


    {{-- ✅ DEADLINE TOAST NOTIFICATION --}}
    <div id="deadline-toast-container"
    style="position:fixed;bottom:20px;right:20px;z-index:99999;display:flex;flex-direction:column;gap:8px;width:350px;">
    </div>

    <script>
        function showDeadlineToasts(notifications) {
            const container = document.getElementById('deadline-toast-container');
            if (!container) return;

            notifications.forEach((notif, i) => {
                if (notif.type !== 'deadline_warning') return;

                const toast = document.createElement('div');
                toast.style.cssText =
                    'display:flex;align-items:flex-start;gap:12px;background:white;border:1px solid #fca5a5;box-shadow:0 10px 25px rgba(0,0,0,0.1);border-radius:12px;padding:12px 16px;transition:all 0.5s ease;opacity:0;transform:translateY(10px);pointer-events:auto;margin-bottom:4px;';
                toast.innerHTML = `
                <div style="width:32px;height:32px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px;">
                    <i class="fa-solid fa-clock" style="color:#ef4444;font-size:12px;"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <p style="font-size:12px;font-weight:700;color:#1f2937;margin:0;">${notif.title}</p>
                    <p style="font-size:12px;color:#6b7280;margin:4px 0 0;">${notif.message}</p>
                    <p style="font-size:10px;color:#9ca3af;margin:4px 0 0;">${notif.time}</p>
                </div>
                <button onclick="this.parentElement.remove()" style="color:#d1d5db;background:none;border:none;cursor:pointer;flex-shrink:0;font-size:14px;">✕</button>
            `;

                container.appendChild(toast);

                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        toast.style.opacity = '1';
                        toast.style.transform = 'translateY(0)';
                    });
                });

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(10px)';
                    setTimeout(() => toast.remove(), 500);
                }, 7000 + (i * 200));
            });
        }

        function pollDeadlineToast() {
            fetch('{{ route('notifications.poll') }}', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const deadlineNotifs = (data.notifications || []).filter(n => n.type === 'deadline_warning');
                    if (deadlineNotifs.length > 0) {
                        showDeadlineToasts(deadlineNotifs);
                    }
                })
                .catch(() => {});
        }

@if(request()->routeIs('tasks.*') || request()->routeIs('projects.*'))
            document.addEventListener('DOMContentLoaded', function() {
                setTimeout(pollDeadlineToast, 1200);
            });
        @endif
    </script>

</body>

</html>
