<div
    id="project-tab-members"
    class="project-tab-content relative
        {{ $currentTab !== 'members' ? 'hidden' : '' }}"
>
    <div class="p-6">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-2xl font-bold text-gray-900">
                    Project Members
                </h3>

                <p class="mt-1 text-sm text-gray-500">
                    Manage your project team members.
                </p>
            </div>

            <div class="rounded-lg bg-[#ADE8F4] px-4 py-2 text-sm font-semibold text-gray-700">
                {{ $project->members->count() }}
                {{ Str::plural('Member', $project->members->count()) }}
            </div>
        </div>

        @include('projects.partials.show._member-form')

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            @include('projects.partials.show._member-group', [
                'groupMembers' => $managers,
                'groupKey' => 'manager',
                'title' => 'Admins',
                'iconClass' => 'fa-solid fa-shield-halved',
                'backgroundClass' => 'bg-purple-50/50',
                'borderClass' => 'border-purple-200',
                'titleClass' => 'text-purple-900',
                'countClass' => 'bg-purple-200 text-purple-800',
            ])

            @include('projects.partials.show._member-group', [
                'groupMembers' => $members,
                'groupKey' => 'member',
                'title' => 'Members',
                'iconClass' => 'fa-solid fa-user-group',
                'backgroundClass' => 'bg-blue-50/50',
                'borderClass' => 'border-blue-200',
                'titleClass' => 'text-blue-900',
                'countClass' => 'bg-blue-200 text-blue-800',
            ])

            @include('projects.partials.show._member-group', [
                'groupMembers' => $viewers,
                'groupKey' => 'viewer',
                'title' => 'Viewers',
                'iconClass' => 'fa-regular fa-eye',
                'backgroundClass' => 'bg-yellow-50/70',
                'borderClass' => 'border-yellow-200',
                'titleClass' => 'text-yellow-700',
                'countClass' => 'bg-yellow-300 text-yellow-900',
            ])
        </div>
    </div>
</div>
