@php
    $isCurrentUser = (int) $member->id === (int) $currentUserId;
    $isWorkspaceOwner = (int) $member->id === (int) $workspaceOwnerId;
    $isProjectCreator = (int) $member->id === (int) $project->created_by;

    $isOverloaded = in_array(
        (int) $member->id,
        array_map('intval', $overloadedMemberIds),
        true
    );

    $taskCount = (int) ($memberTaskCounts[$member->id] ?? 0);

    $canRemoveMember =
        $canManageMembers
        && ! $isCurrentUser
        && ! $isWorkspaceOwner
        && (
            ! $isProjectCreator
            || (int) $currentUserId === (int) $workspaceOwnerId
        );

    $dropdownId = "project-member-dropdown-{$groupKey}-{$member->id}";
    $roleMenuId = "project-member-role-menu-{$groupKey}-{$member->id}";
@endphp

<div class="rounded-lg border border-gray-200 bg-white p-3 transition-shadow hover:shadow-md">
    <div class="flex items-center gap-2">
        @if ($member->profile_photo)
            <img
                src="{{ asset('storage/' . $member->profile_photo) }}"
                alt="{{ $member->name }}"
                class="h-8 w-8 flex-shrink-0 rounded-full object-cover"
            >
        @else
            <div
                class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full
                    bg-gradient-to-br from-indigo-400 to-purple-400 text-xs font-bold text-white"
            >
                {{ strtoupper(substr($member->name, 0, 1)) }}
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-semibold text-gray-900">
                {{ $member->name }}
            </p>

            @if ($member->job_title)
                <p class="truncate text-xs text-gray-400">
                    {{ $member->job_title }}
                </p>
            @endif

            @if ($isOverloaded)
                <span
                    class="mt-1 inline-flex items-center gap-1 rounded-full bg-red-100
                        px-2 py-0.5 text-xs font-semibold text-red-600"
                >
                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                    Overload ({{ $taskCount }} tasks)
                </span>
            @endif
        </div>

        <div class="flex flex-shrink-0 flex-wrap items-center justify-end gap-1">
            @if ($isWorkspaceOwner)
                <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-700">
                    <i class="fa-solid fa-crown mr-0.5 text-[10px]"></i>
                    Owner
                </span>
            @elseif ($isProjectCreator)
                <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">
                    <i class="fa-solid fa-star mr-0.5 text-[10px]"></i>
                    Creator
                </span>
            @endif

            @if ($isCurrentUser)
                <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-600">
                    You
                </span>
            @endif
        </div>

        @if ($canRemoveMember)
            <div class="relative flex-shrink-0">
                <button
                    type="button"
                    onclick="toggleProjectMemberDropdown('{{ $dropdownId }}')"
                    class="flex h-7 w-7 items-center justify-center rounded-md border border-gray-200
                        text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Member actions"
                >
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>

                <div
                    id="{{ $dropdownId }}"
                    class="project-member-dropdown absolute right-0 top-full z-50 mt-1 hidden w-44
                        overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-lg"
                >
                    <button
                        type="button"
                        onclick="toggleProjectMemberRoleMenu('{{ $roleMenuId }}')"
                        class="flex w-full items-center justify-between gap-2 px-3 py-2
                            text-sm text-gray-700 transition-colors hover:bg-gray-50"
                    >
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-user-gear text-indigo-500"></i>
                            Change Role
                        </span>

                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                    </button>

                    <div
                        id="{{ $roleMenuId }}"
                        class="hidden border-t border-gray-100"
                    >
                        @foreach ([
                            'manager' => 'Manager',
                            'member' => 'Member',
                            'viewer' => 'Viewer',
                        ] as $roleValue => $roleLabel)
                            @if ($member->pivot->role !== $roleValue)
                                <form
                                    method="POST"
                                    action="{{ route('projects.members.update', [
                                        $project->token,
                                        $member->id,
                                    ]) }}"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <input
                                        type="hidden"
                                        name="role"
                                        value="{{ $roleValue }}"
                                    >

                                    <button
                                        type="submit"
                                        class="w-full px-3 py-2 pl-9 text-left text-sm
                                            text-gray-600 transition-colors hover:bg-gray-50"
                                    >
                                        {{ $roleLabel }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>

                    <div class="my-1 border-t border-gray-100"></div>

                    <form
                        method="POST"
                        action="{{ route('projects.members.destroy', [
                            $project->token,
                            $member->id,
                        ]) }}"
                        onsubmit="return confirm('Hapus member ini dari project?');"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="flex w-full items-center gap-2 px-3 py-2 text-sm
                                text-red-600 transition-colors hover:bg-red-50"
                        >
                            <i class="fa-regular fa-trash-can"></i>
                            Delete
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
