<div class="flex flex-col h-full" style="background-color: #ffffff">

    {{-- LOGO --}}
    <div class="flex-shrink-0 flex flex-col items-center justify-center px-4 py-6 gap-2">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-2">
            @include('layouts.logo', ['class' => 'h-28'])
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
            <a href="{{ route('activity.log') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
    {{ request()->routeIs('activity.log') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('activity.log') ? 'background-color: #0096c7' : '' }}">
                <i class="fa-solid fa-clock-rotate-left w-5 text-center text-sm"></i>
                <span class="font-medium text-sm">Activity Log</span>
            </a>
            <a href="{{ route('discussion.index') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
                {{ request()->routeIs('discussion.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('discussion.*') ? 'background-color: #0096c7' : '' }}">

                <div class="relative w-5 flex justify-center flex-shrink-0">
                    <i class="fa-solid fa-comments text-center text-sm"></i>
                    <span id="discussion-badge"
                        class="absolute -top-2 -right-3 flex items-center justify-center
                        min-w-[16px] h-4 px-1 text-[9px] font-bold rounded-full
                        bg-red-500 text-white ring-2
                        {{ request()->routeIs('discussion.*') ? 'ring-[#0096c7]' : 'ring-white' }}
                        {{ ($sidebarUnreadDiscussion ?? 0) > 0 ? '' : 'hidden' }}">
                        {{ ($sidebarUnreadDiscussion ?? 0) > 99 ? '99+' : $sidebarUnreadDiscussion ?? 0 }}
                    </span>
                </div>

                <span class="font-medium text-sm">Discussion</span>
            </a>
            <a href="{{ route('overload.index') }}"
                class="flex items-center gap-3 px-4 py-2.5 rounded-xl transition-all duration-200
    {{ request()->routeIs('overload.index') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                style="{{ request()->routeIs('overload.index') ? 'background-color: #0096c7' : '' }}">
                <i class="fa-solid fa-file-circle-exclamation"></i>
                <span class="font-medium text-sm">Overload</span>
            </a>
        </nav>
        @php
            $pengaturanActive =
                request()->routeIs('managementprojects.*') ||
                request()->routeIs('users.*') ||
                request()->routeIs('management-users.*') ||
                request()->routeIs('managementworkspaces.*') ||
                request()->routeIs('management-roles.*') ||
                request()->routeIs('management-permissions.*');
        @endphp

        @if (auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isPermissionSystemReady()))
            @canany(['management-users.view', 'management-projects.view', 'management-workspaces.view', 'roles.view', 'permissions.view'])
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
                <div id="pengaturan-menu" class="space-y-1 {{ $pengaturanActive ? '' : 'hidden' }}">

                    @can('management-workspaces.view')
                        <a href="{{ route('managementworkspaces.index') }}"
                        class="flex items-center gap-3 pl-8 py-2.5 rounded-xl transition-all duration-200 text-sm
            {{ request()->routeIs('managementworkspaces.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('managementworkspaces.*') ? 'background-color: #0096c7' : '' }}">
                        <span class="w-5 flex justify-center ml-6">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('managementworkspaces.*') ? 'bg-white' : 'bg-gray-400' }}"></span>
                        </span>
                        <span class="font-medium">Management Workspaces</span>
                        </a>
                    @endcan

                    @can('management-projects.view')
                        <a href="{{ route('managementprojects.index') }}"
                        class="flex items-center gap-3 pl-8 py-2.5 rounded-xl transition-all duration-200 text-sm
            {{ request()->routeIs('managementprojects.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('managementprojects.*') ? 'background-color: #0096c7' : '' }}">
                        <span class="w-5 flex justify-center ml-6">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('managementprojects.*') ? 'bg-white' : 'bg-gray-400' }}"></span>
                        </span>
                        <span class="font-medium">Management Projects</span>
                        </a>
                    @endcan

                    @can('management-users.view')
                        <a href="{{ route('management-users.index') }}"
                        class="flex items-center gap-3 pl-8 py-2.5 rounded-xl transition-all duration-200 text-sm
            {{ request()->routeIs('management-users.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('management-users.*') ? 'background-color: #0096c7' : '' }}">
                        <span class="w-5 flex justify-center ml-6">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('management-users.*') ? 'bg-white' : 'bg-gray-400' }}"></span>
                        </span>
                        <span class="font-medium">Management Users</span>
                        </a>
                    @endcan

                    @can('roles.view')
                        <a href="{{ route('management-roles.index') }}"
                        class="flex items-center gap-3 pl-8 py-2.5 rounded-xl transition-all duration-200 text-sm
            {{ request()->routeIs('management-roles.*', 'management-permissions.*') ? 'text-white shadow-lg' : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('management-roles.*', 'management-permissions.*') ? 'background-color: #0096c7' : '' }}">
                        <span class="w-5 flex justify-center ml-6">
                            <span
                                class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('management-roles.*', 'management-permissions.*') ? 'bg-white' : 'bg-gray-400' }}"></span>
                        </span>
                        <span class="font-medium">Role &amp; Permissions</span>
                        </a>
                    @endcan
                </div>
            </nav>
            @endcanany
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

        function pollDiscussionBadge() {
            fetch('{{ route('discussion.unread-sidebar') }}', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                    },
                    credentials: 'same-origin',
                })
                .then(r => r.ok ? r.json() : null)
                .then(data => {
                    if (!data) return;
                    const badge = document.getElementById('discussion-badge');
                    if (!badge) return;

                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.classList.remove('hidden');
                    } else {
                        badge.classList.add('hidden');
                    }
                })
                .catch(() => {});
        }

        setTimeout(() => {
            pollDiscussionBadge();
            setInterval(pollDiscussionBadge, 30000);
        }, 15000);
    });
</script>
