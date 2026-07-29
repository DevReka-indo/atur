<div class="flex h-full flex-col bg-white">

    {{-- Logo --}}
    <div class="flex flex-shrink-0 flex-col items-center justify-center gap-2 px-4 py-6">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-2">
            @include('layouts.logo', ['class' => 'h-28'])
        </a>
    </div>

    @php
        $templateActive =
            request()->routeIs('project-templates.*') ||
            request()->routeIs('project-template-categories.*');

        $templateGalleryActive = request()->routeIs('template-gallery.*');

        $administrationActive =
            request()->routeIs('managementworkspaces.*') ||
            request()->routeIs('managementprojects.*') ||
            request()->routeIs('management-users.*') ||
            request()->routeIs('management-roles.*') ||
            request()->routeIs('management-permissions.*');

        $settingsActive =
            request()->routeIs('settings.account') ||
            request()->routeIs('settings.about');
    @endphp

    {{-- Scrollable Area --}}
    <div class="flex-1 overflow-y-auto pb-6">

        {{-- Menu Utama --}}
        <div>
            <p class="px-4 pb-2 pt-4 text-xs font-semibold uppercase tracking-wider text-gray-500">
                Menu Utama
            </p>

            <nav class="space-y-1 px-4">

                {{-- Dashboard --}}
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('dashboard')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('dashboard') ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-gauge w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        Dashboard
                    </span>
                </a>

                {{-- Workspaces --}}
                <a href="{{ route('workspaces.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('workspaces.*')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('workspaces.*') ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-layer-group w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        Workspaces
                    </span>
                </a>

                {{-- Projects --}}
                <a href="{{ route('projects.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('projects.*')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('projects.*') ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-diagram-project w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        Projects
                    </span>
                </a>

            </nav>
        </div>

        {{-- Aktivitas --}}
        <div>
            <p class="px-4 pb-2 pt-6 text-xs font-semibold uppercase tracking-wider text-gray-500">
                Aktivitas
            </p>

            <nav class="space-y-1 px-4">

                {{-- My Tasks --}}
                <a href="{{ route('tasks.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('tasks.*')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('tasks.*') ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-list-check w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        My Tasks
                    </span>
                </a>

                {{-- Project Discussions --}}
                <a href="{{ route('discussion.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('discussion.*')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('discussion.*') ? 'background-color: #0096c7' : '' }}">

                    <div class="relative flex w-5 flex-shrink-0 justify-center">
                        <i class="fa-solid fa-comments text-center text-sm"></i>

                        <span id="discussion-badge"
                            class="absolute -right-3 -top-2 flex h-4 min-w-[16px] items-center justify-center
                                rounded-full bg-red-500 px-1 text-[9px] font-bold text-white ring-2
                                {{ request()->routeIs('discussion.*') ? 'ring-[#0096c7]' : 'ring-white' }}
                                {{ ($sidebarUnreadDiscussion ?? 0) > 0 ? '' : 'hidden' }}">

                            {{ ($sidebarUnreadDiscussion ?? 0) > 99
                                ? '99+'
                                : ($sidebarUnreadDiscussion ?? 0) }}
                        </span>
                    </div>

                    <span class="text-sm font-medium">
                        Project Discussions
                    </span>
                </a>

                {{-- Activity Log --}}
                <a href="{{ route('activity.log') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('activity.log')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('activity.log') ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-clock-rotate-left w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        Activity Log
                    </span>
                </a>

                {{-- Overload --}}
                <a href="{{ route('overload.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ request()->routeIs('overload.*')
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ request()->routeIs('overload.*') ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-chart-column w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        Overload
                    </span>
                </a>

            </nav>
        </div>

        {{-- Project Template --}}
        <div>
            <p class="px-4 pb-2 pt-6 text-xs font-semibold uppercase tracking-wider text-gray-500">
                Template
            </p>

            <nav class="space-y-1 px-4">
                <a href="{{ route('template-gallery.index') }}"
                    class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                        {{ $templateGalleryActive
                            ? 'text-white shadow-lg'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                    style="{{ $templateGalleryActive ? 'background-color: #0096c7' : '' }}">

                    <i class="fa-solid fa-table-cells-large w-5 text-center text-sm"></i>

                    <span class="text-sm font-medium">
                        Template Gallery
                    </span>
                </a>

                @if (auth()->check() && auth()->user()->isPermissionSystemReady())
                    @canany(['project-template-categories.view', 'project-templates.view'])
                        <a href="{{ auth()->user()->can('project-templates.view')
                            ? route('project-templates.index')
                            : route('project-template-categories.index') }}"
                            class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                                {{ $templateActive
                                    ? 'text-white shadow-lg'
                                    : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                            style="{{ $templateActive ? 'background-color: #0096c7' : '' }}">

                            <i class="fa-solid fa-copy w-5 text-center text-sm"></i>

                            <span class="text-sm font-medium">
                                Project Templates
                            </span>
                        </a>
                    @endcanany
                @endif
            </nav>
        </div>

        {{-- Administrasi --}}
        @if (auth()->check() && auth()->user()->isPermissionSystemReady())
            @canany([
                'management-users.view',
                'management-projects.view',
                'management-workspaces.view',
                'roles.view',
                'permissions.view',
            ])
                <div>
                    <p class="px-4 pb-2 pt-6 text-xs font-semibold uppercase tracking-wider text-gray-500">
                        Administrasi
                    </p>

                    <nav class="space-y-1 px-4">

                        {{-- Management Workspaces --}}
                        @can('management-workspaces.view')
                            <a href="{{ route('managementworkspaces.index') }}"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                                    {{ request()->routeIs('managementworkspaces.*')
                                        ? 'text-white shadow-lg'
                                        : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                                style="{{ request()->routeIs('managementworkspaces.*')
                                    ? 'background-color: #0096c7'
                                    : '' }}">

                                <i class="fa-solid fa-briefcase w-5 text-center text-sm"></i>

                                <span class="text-sm font-medium">
                                    Management Workspaces
                                </span>
                            </a>
                        @endcan

                        {{-- Management Projects --}}
                        @can('management-projects.view')
                            <a href="{{ route('managementprojects.index') }}"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                                    {{ request()->routeIs('managementprojects.*')
                                        ? 'text-white shadow-lg'
                                        : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                                style="{{ request()->routeIs('managementprojects.*')
                                    ? 'background-color: #0096c7'
                                    : '' }}">

                                <i class="fa-solid fa-folder-tree w-5 text-center text-sm"></i>

                                <span class="text-sm font-medium">
                                    Management Projects
                                </span>
                            </a>
                        @endcan

                        {{-- Management Users --}}
                        @can('management-users.view')
                            <a href="{{ route('management-users.index') }}"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                                    {{ request()->routeIs('management-users.*')
                                        ? 'text-white shadow-lg'
                                        : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                                style="{{ request()->routeIs('management-users.*')
                                    ? 'background-color: #0096c7'
                                    : '' }}">

                                <i class="fa-solid fa-users-gear w-5 text-center text-sm"></i>

                                <span class="text-sm font-medium">
                                    Management Users
                                </span>
                            </a>
                        @endcan

                        {{-- Role & Permissions --}}
                        @canany(['roles.view', 'permissions.view'])
                            <a href="{{ auth()->user()->can('roles.view')
                                ? route('management-roles.index')
                                : route('management-permissions.index') }}"
                                class="flex items-center gap-3 rounded-xl px-4 py-2.5 transition-all duration-200
                                    {{ request()->routeIs(
                                        'management-roles.*',
                                        'management-permissions.*',
                                    )
                                        ? 'text-white shadow-lg'
                                        : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                                style="{{ request()->routeIs(
                                    'management-roles.*',
                                    'management-permissions.*',
                                )
                                    ? 'background-color: #0096c7'
                                    : '' }}">

                                <i class="fa-solid fa-shield-halved w-5 text-center text-sm"></i>

                                <span class="text-sm font-medium">
                                    Role &amp; Permissions
                                </span>
                            </a>
                        @endcanany

                    </nav>
                </div>
            @endcanany
        @endif

        {{-- Pengaturan --}}
        <div>
            <p class="px-4 pb-2 pt-6 text-xs font-semibold uppercase tracking-wider text-gray-500">
                Pengaturan
            </p>

            <nav class="space-y-1 px-4">

                <button id="settings-sidebar-toggle" type="button"
                    class="flex w-full items-center justify-between rounded-xl px-4 py-2.5
                        transition-all duration-200
                        {{ $settingsActive
                            ? 'bg-gray-900/5 text-gray-900'
                            : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}">

                    <span class="flex items-center gap-3 text-sm font-medium">
                        <i class="fa-solid fa-gear w-5 text-center text-sm"></i>
                        Settings
                    </span>

                    <span id="settings-sidebar-arrow"
                        class="transition-transform duration-300
                            {{ $settingsActive ? 'rotate-180' : '' }}">

                        <i class="fa-solid fa-angle-down text-xs"></i>
                    </span>
                </button>

                <div id="settings-sidebar-menu"
                    class="space-y-1 pt-1 {{ $settingsActive ? '' : 'hidden' }}">

                    {{-- Account Settings --}}
                    <a href="{{ route('settings.account') }}"
                        class="flex items-center gap-3 rounded-xl py-2.5 pl-10 pr-3 text-sm
                            transition-all duration-200
                            {{ request()->routeIs('settings.account')
                                ? 'text-white shadow-lg'
                                : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('settings.account')
                            ? 'background-color: #0096c7'
                            : '' }}">

                        <i class="fa-solid fa-user-gear w-5 text-center text-sm"></i>

                        <span class="font-medium">
                            Account Settings
                        </span>
                    </a>

                    {{-- Tentang Aplikasi --}}
                    <a href="{{ route('settings.about') }}"
                        class="flex items-center gap-3 rounded-xl py-2.5 pl-10 pr-3 text-sm
                            transition-all duration-200
                            {{ request()->routeIs('settings.about')
                                ? 'text-white shadow-lg'
                                : 'text-gray-600 hover:bg-gray-900/10 hover:text-gray-900' }}"
                        style="{{ request()->routeIs('settings.about')
                            ? 'background-color: #0096c7'
                            : '' }}">

                        <i class="fa-solid fa-circle-info w-5 text-center text-sm"></i>

                        <span class="font-medium">
                            Tentang Aplikasi
                        </span>
                    </a>

                </div>
            </nav>
        </div>

    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const settingsToggle = document.getElementById('settings-sidebar-toggle');
        const settingsMenu = document.getElementById('settings-sidebar-menu');
        const settingsArrow = document.getElementById('settings-sidebar-arrow');

        settingsToggle?.addEventListener('click', () => {
            settingsMenu?.classList.toggle('hidden');
            settingsArrow?.classList.toggle('rotate-180');
        });

        function pollDiscussionBadge() {
            fetch('{{ route('discussion.unread-sidebar') }}', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            })
                .then((response) => response.ok ? response.json() : null)
                .then((data) => {
                    if (!data) {
                        return;
                    }

                    const badge = document.getElementById('discussion-badge');

                    if (!badge) {
                        return;
                    }

                    if (data.count > 0) {
                        badge.textContent = data.count > 99 ? '99+' : data.count;
                        badge.classList.remove('hidden');

                        return;
                    }

                    badge.classList.add('hidden');
                })
                .catch(() => {
                    // Polling discussion tidak boleh mengganggu sidebar.
                });
        }

        setTimeout(() => {
            pollDiscussionBadge();
            setInterval(pollDiscussionBadge, 30000);
        }, 15000);
    });
</script>
