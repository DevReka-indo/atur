<div class="flex items-center gap-2">
    @if ($canCreateProject)
        <div id="action-create-project">
            <a href="{{ route('projects.create') }}?workspace_id={{ $workspace->id }}"
                class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                    bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30
                    transition-all duration-300">
                <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                Create Project
            </a>
        </div>
    @endif

    @if ($canManageMembers)
        <div id="action-invite-member" style="display: none;">
            <button type="button"
                data-open-workspace-invite
                class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                    bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30
                    transition-all duration-300">
                <i class="fa-solid fa-user-plus mr-2 transition-transform group-hover:rotate-110"></i>
                Invite Member
            </button>
        </div>
    @endif
</div>
