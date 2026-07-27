@php
    $isCurrentUser = (int) $member->id === (int) $currentUserId;
    $isWorkspaceOwner = (int) $member->id === (int) $workspaceOwnerId;
    $isProjectCreator = (int) $member->id === (int) $project->created_by;
    $isOverloaded = in_array((int) $member->id, array_map('intval', $overloadedMemberIds), true);
    $taskCount = (int) ($memberTaskCounts[$member->id] ?? 0);
    $canManageMemberActions = $canManageMembers
        && ! $isCurrentUser
        && ! $isWorkspaceOwner
        && (! $isProjectCreator || (int) $currentUserId === (int) $workspaceOwnerId);
    $dropdownId = "project-member-dropdown-{$groupRole}-{$member->id}";
    $roleMenuId = "project-member-role-menu-{$groupRole}-{$member->id}";
    $memberRole = $member->pivot->role;
@endphp

<article class="rounded-lg border border-gray-200 bg-white p-3 transition-shadow hover:shadow-md"
    data-project-member-id="{{ $member->id }}">
    <div class="flex items-start gap-3">
        @if ($member->profile_photo)
            <img src="{{ asset('storage/'.$member->profile_photo) }}" alt="{{ $member->name }}"
                class="h-9 w-9 shrink-0 rounded-full object-cover">
        @else
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-xs font-bold text-white">
                {{ strtoupper(substr($member->name, 0, 1)) }}
            </div>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-1.5">
                <p class="truncate text-sm font-semibold text-gray-900">{{ $member->name }}</p>
                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600">
                    {{ $projectRoleLabels[$memberRole] }}
                </span>
                @if ($isWorkspaceOwner)
                    <span class="rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-semibold text-amber-700">
                        <i class="fa-solid fa-crown mr-0.5 text-[9px]"></i>
                        Workspace Owner
                    </span>
                @elseif ($isProjectCreator)
                    <span class="rounded-full bg-green-100 px-2 py-0.5 text-[11px] font-semibold text-green-700">
                        <i class="fa-solid fa-star mr-0.5 text-[9px]"></i>
                        Creator
                    </span>
                @endif
                @if ($isCurrentUser)
                    <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-semibold text-indigo-600">
                        You
                    </span>
                @endif
            </div>

            <p class="mt-0.5 truncate text-xs text-gray-500">{{ $member->email }}</p>
            @if ($member->job_title)
                <p class="truncate text-xs text-gray-400">{{ $member->job_title }}</p>
            @endif

            @if ($isOverloaded)
                <span class="mt-1 inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-600">
                    <i class="fa-solid fa-triangle-exclamation text-[10px]"></i>
                    Overload ({{ $taskCount }} tasks)
                </span>
            @endif
        </div>

        @if ($canManageMemberActions)
            <div class="relative shrink-0">
                <button type="button"
                    class="flex h-8 w-8 items-center justify-center rounded-md border border-gray-200 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600"
                    aria-label="Actions for {{ $member->name }}"
                    aria-controls="{{ $dropdownId }}"
                    aria-expanded="false"
                    data-member-menu-trigger="{{ $dropdownId }}">
                    <i class="fa-solid fa-ellipsis-vertical"></i>
                </button>

                <div id="{{ $dropdownId }}"
                    class="absolute right-0 top-full z-50 mt-1 hidden w-48 overflow-hidden rounded-xl border border-gray-100 bg-white py-1 shadow-lg"
                    data-member-menu>
                    <button type="button"
                        class="flex w-full items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                        aria-controls="{{ $roleMenuId }}"
                        aria-expanded="false"
                        data-role-menu-trigger="{{ $roleMenuId }}">
                        <span class="flex items-center gap-2">
                            <i class="fa-solid fa-user-gear text-indigo-500"></i>
                            Change Role
                        </span>
                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                    </button>

                    <div id="{{ $roleMenuId }}" class="hidden border-t border-gray-100 p-3" data-role-menu>
                        <form method="POST"
                            action="{{ route('projects.members.update', [$project->token, $member->id]) }}">
                            @csrf
                            @method('PATCH')
                            <label for="project-member-role-{{ $member->id }}" class="sr-only">
                                Project role for {{ $member->name }}
                            </label>
                            <select id="project-member-role-{{ $member->id }}" name="role"
                                class="w-full rounded-lg border-slate-300 py-2 text-xs focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach ($projectRoleLabels as $roleValue => $roleLabel)
                                    <option value="{{ $roleValue }}" @selected($memberRole === $roleValue)>
                                        {{ $roleLabel }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit"
                                class="mt-2 w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-indigo-700">
                                Update Role
                            </button>
                        </form>
                    </div>

                    <div class="my-1 border-t border-gray-100"></div>

                    <form method="POST"
                        action="{{ route('projects.members.destroy', [$project->token, $member->id]) }}"
                        data-remove-project-member
                        data-confirm-message="Remove {{ $projectRoleLabels[$memberRole] }} {{ $member->name }} from this project?">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="flex w-full items-center gap-2 px-3 py-2 text-sm text-red-600 transition-colors hover:bg-red-50">
                            <i class="fa-regular fa-trash-can"></i>
                            Remove
                        </button>
                    </form>
                </div>
            </div>
        @endif
    </div>
</article>
