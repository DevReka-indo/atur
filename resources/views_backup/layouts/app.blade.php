<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }}</title>

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

<body class="bg-gray-50 font-sans text-gray-800">
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
                                class="w-64 pl-10 pr-4 py-2 rounded-xl border border-gray-200
                                      focus:ring-2 focus:ring-cyan-500 focus:outline-none text-sm"
                                autocomplete="off">

                            <div id="search-results"
                                class="absolute right-0 mt-2 w-72 bg-white border border-gray-200
                                    rounded-xl shadow-xl hidden z-50 max-h-80 overflow-y-auto">
                            </div>
                        </div>

                        @php
                            $unreadCount = \App\Models\UserNotification::where('user_id', auth()->id())
                                ->whereNull('read_at')
                                ->count();
                        @endphp

                        <a href="{{ route('notifications.index') }}" style="position:relative;">
                            <i class="fas fa-bell"></i>

                            @if ($unreadCount > 0)
                                <span
                                    style="
            position:absolute;
            top:-5px;
            right:-8px;
            background:red;
            color:white;
            border-radius:50%;
            padding:2px 6px;
            font-size:11px;">
                                    {{ $unreadCount }}
                                </span>
                            @endif
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
                                <button id="profile-btn" type="button">
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

                                    <a href="{{ route('profile.edit') }}" class="block px-5 py-3 text-sm hover:bg-gray-100">
                                        <i class="fas fa-user mr-2"></i> Profile
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

            if (searchInput) {
                let debounceTimer;

                searchInput.addEventListener("keyup", function() {
                    clearTimeout(debounceTimer);
                    let query = this.value.trim();

                    if (query.length < 2) {
                        resultsBox.classList.add("hidden");
                        return;
                    }

                    debounceTimer = setTimeout(function() {

                        fetch("{{ route('live.search') }}?q=" + encodeURIComponent(query))
                            .then(response => response.json())
                            .then(data => {

                                resultsBox.innerHTML = "";
                                resultsBox.classList.remove("hidden");

                                if (!data.workspaces.length && !data.projects.length && !data
                                    .tasks.length) {
                                    resultsBox.innerHTML =
                                        '<div class="p-4 text-sm text-gray-400">No results found</div>';
                                    return;
                                }

                                if (data.workspaces.length) {
                                    resultsBox.innerHTML +=
                                        '<div class="px-4 py-2 text-xs text-gray-400 uppercase">Workspaces</div>';
                                    data.workspaces.forEach(workspace => {
                                        resultsBox.innerHTML +=
                                            `<a href="/workspaces/${workspace.id}"
                    class="block px-4 py-2 text-sm hover:bg-emerald-50">
                    ${workspace.name}
                 </a>`;
                                    });
                                }

                                if (data.projects.length) {
                                    resultsBox.innerHTML +=
                                        '<div class="px-4 py-2 text-xs text-gray-400 uppercase">Projects</div>';
                                    data.projects.forEach(project => {
                                        resultsBox.innerHTML +=
                                            `<a href="/projects/${project.id}"
                    class="block px-4 py-2 text-sm hover:bg-cyan-50">
                    ${project.name}
                 </a>`;
                                    });
                                }

                                if (data.tasks.length) {
                                    resultsBox.innerHTML +=
                                        '<div class="px-4 py-2 text-xs text-gray-400 uppercase">Tasks</div>';
                                    data.tasks.forEach(task => {
                                        resultsBox.innerHTML +=
                                            `<a href="/tasks/${task.id}"
                    class="block px-4 py-2 text-sm hover:bg-blue-50">
                    ${task.name}
                 </a>`;
                                    });
                                }

                            })
                            .catch(error => console.log(error));

                    }, 300);
                });

                document.addEventListener("click", function(e) {
                    if (!searchInput.contains(e.target)) {
                        resultsBox.classList.add("hidden");
                    }
                });
            }

        });
    </script>

    @stack('scripts')


    {{-- ===================== INVITATION BANNER ===================== --}}
    @if (session('invitation_token'))
        @php
            $invToken = session('invitation_token');
            $invData = \App\Models\Invitation::with('inviter')
                ->where('token', $invToken)
                ->where('status', 'pending')
                ->first();
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
    {{-- ============================================================= --}}

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

        document.getElementById('inviteModal').addEventListener('click', function(e) {
            if (e.target === this) this.classList.add('hidden');
        });

        @if (session('invite_success') || session('invite_error'))
            document.getElementById('inviteModal').classList.remove('hidden');
        @endif
    </script>
</body>

</html>
