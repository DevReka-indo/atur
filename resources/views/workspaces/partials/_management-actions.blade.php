@php($activeTab = $activeTab ?? null)

<div class="flex items-center gap-2">
    @if (($activeTab === null || $activeTab === 'overview') && $canCreateProject)
        <a href="{{ route('projects.create') }}?workspace_id={{ $workspace->id }}"
            class="group inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white shadow-lg shadow-indigo-500/30 transition-all duration-300 hover:bg-indigo-700">
            <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
            Create Project
        </a>
    @endif

    @if (($activeTab === null || $activeTab === 'members') && $canManageMembers)
        <button type="button" data-open-workspace-invite
            class="group inline-flex items-center rounded-xl bg-indigo-600 px-5 py-2.5 font-medium text-white shadow-lg shadow-indigo-500/30 transition-all duration-300 hover:bg-indigo-700">
            <i class="fa-solid fa-user-plus mr-2 transition-transform group-hover:rotate-110"></i>
            Invite Member
        </button>
    @endif
</div>
