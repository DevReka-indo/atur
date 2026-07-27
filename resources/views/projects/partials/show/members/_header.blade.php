<div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h3 class="text-2xl font-bold text-gray-900">Project Members</h3>
        <p class="mt-1 text-sm text-gray-500">Manage project access and roles.</p>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <div class="rounded-lg bg-[#ADE8F4] px-4 py-2 text-sm font-semibold text-gray-700">
            {{ $project->members->count() }}
            {{ Str::plural('Member', $project->members->count()) }}
        </div>

        @if ($canManageMembers)
            <button type="button"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-md shadow-indigo-500/20 transition hover:bg-indigo-700"
                data-open-member-invite>
                <i class="fa-solid fa-user-plus"></i>
                Invite Member
            </button>
        @endif
    </div>
</div>
