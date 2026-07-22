@props(['active'])

<div class="flex flex-wrap gap-2 mb-6 border-b border-gray-200">
    @can('roles.view')
        <a href="{{ route('management-roles.index') }}"
            class="inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors {{ $active === 'roles' ? 'border-sky-600 text-sky-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-user-shield"></i>
            Roles
        </a>
    @endcan
    @can('permissions.view')
        <a href="{{ route('management-permissions.index') }}"
            class="inline-flex items-center gap-2 px-4 py-3 text-sm font-semibold border-b-2 transition-colors {{ $active === 'permissions' ? 'border-sky-600 text-sky-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            <i class="fa-solid fa-key"></i>
            Permissions
        </a>
    @endcan
</div>
