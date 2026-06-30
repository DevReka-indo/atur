<div class="flex flex-col h-full" style="background-color: #ffff">

    {{-- LOGO --}}
    <div class="flex-shrink-0 flex flex-col items-center justify-center px-4 py-6 gap-2">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-2">
            @include('layouts.logo', ['class' => 'h-20'])
            <div class="text-center leading-tight">
                <p class="text-xs text-gray-600">Project Management</p>
            </div>
        </a>
    </div>

    {{-- HEADER --}}
    <p class="px-4 pt-4 pb-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">
        menu
    </p>

    {{-- SCROLLABLE AREA --}}
    <div class="flex-1 overflow-y-auto">

        {{-- MENU --}}
        <nav class="px-4 py-2 space-y-1">
            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
                    {{ request()->routeIs('dashboard') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('dashboard') ? 'background-color: #0096c7' : '' }}">
                <i class="fa-solid fa-gauge w-5 text-center text-sm"></i>
                <span class="font-medium text-sm">Dashboard</span>
            </a>

            <a href="{{ route('workspaces.index') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
                    {{ request()->routeIs('workspaces.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('workspaces.*') ? 'background-color: #0096c7' : '' }}">
                <i class="fa-solid fa-layer-group w-5 text-center text-sm"></i>
                <span class="font-medium text-sm">Workspaces</span>
            </a>

            <a href="{{ route('projects.index') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
                    {{ request()->routeIs('projects.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('projects.*') ? 'background-color: #0096c7' : '' }}">
                <i class="fa-solid fa-diagram-project w-5 text-center text-sm"></i>
                <span class="font-medium text-sm">Projects</span>
            </a>
        </nav>

        <p class="px-4 pt-4 pb-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">
            Overview
        </p>
        <nav class="px-4 py-2 space-y-1">
            <a href="{{ route('tasks.index') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
                {{ request()->routeIs('tasks.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('tasks.*') ? 'background-color: #0096c7' : '' }}">
                <i class="fa-solid fa-list-check w-5 text-center text-sm"></i>
                <span class="font-medium text-sm">My Tasks</span>
            </a>
        </nav>
        @php
            $pengaturanActive =
                request()->routeIs('managementprojects.*') ||
                request()->routeIs('users.*') ||
                request()->routeIs('management-users.*');
        @endphp

        @if (auth()->check() && auth()->user()->isSuperAdmin())
            {{-- SECTION LAINNYA --}}
            <p class="px-4 pt-4 pb-2 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                Lainnya
            </p>

            <nav class="px-4 space-y-1">
                <button id="pengaturan-toggle"
                    class="w-full flex justify-between items-center px-4 py-2.5 rounded-xl hover:bg-gray-900/10 text-gray-600 hover:text-gray-900 transition-all duration-200">
                    <span class="flex items-center gap-3 font-medium text-sm">
                        <i class="fa-solid fa-gear w-5 text-center text-sm"></i>
                        Pengaturan
                    </span>
                    <span id="pengaturan-arrow"
                        class="transition-transform duration-300 {{ $pengaturanActive ? 'rotate-180' : '' }}">
                        <i class="fa-solid fa-angle-down text-xs"></i>
                    </span>
                </button>

                {{-- DROPDOWN --}}
                <div id="pengaturan-menu" class="ml-4 space-y-1 {{ $pengaturanActive ? '' : 'hidden' }}">
                    <a href="{{ route('managementprojects.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 text-sm
                            {{ request()->routeIs('managementprojects.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('managementprojects.*') ? 'background-color: #0096c7' : '' }}">
                        <i class="fa-solid fa-file-zipper"></i>
                        <span class="font-medium">Manajemen Proyek</span>
                    </a>

                    <a href="{{ route('management-users.index') }}"
                        class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200 text-sm
                            {{ request()->routeIs('management-users.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('management-users.*') ? 'background-color: #0096c7' : '' }}">
                        <i class="fa-solid fa-users-gear"></i>
                        <span class="font-medium">Manajemen Pengguna</span>
                    </a>
                </div>
            </nav>
        @endif

    </div>

    {{-- INVITE BUTTON --}}
    {{-- <div class="flex-shrink-0 px-4 py-4">
        <button onclick="document.getElementById('inviteModal').classList.remove('hidden')"
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 text-sm font-semibold rounded-xl transition-all duration-200 hover:opacity-90 hover:scale-[1.02] active:scale-[0.98]"
            style="background: linear-gradient(135deg, #0096c7); color: white; box-shadow: 0 2px 8px rgba(212,163,115,0.4);">
            <i class="fa-solid fa-user-plus text-xs"></i>
            Invite Users
        </button>
    </div> --}}

</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const toggle = document.getElementById('pengaturan-toggle');
        const menu = document.getElementById('pengaturan-menu');
        const arrow = document.getElementById('pengaturan-arrow');

        toggle?.addEventListener('click', () => {
            menu.classList.toggle('hidden');
            arrow.classList.toggle('rotate-180');
        });
    });
</script>
