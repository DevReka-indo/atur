@php
    $memberGroups = [
        [
            'members' => $managers,
            'role' => \App\Models\Project::ROLE_MANAGER,
            'icon' => 'fa-solid fa-shield-halved',
            'background' => 'bg-purple-50/50',
            'border' => 'border-purple-200',
            'title_color' => 'text-purple-900',
            'count_color' => 'bg-purple-200 text-purple-800',
        ],
        [
            'members' => $members,
            'role' => \App\Models\Project::ROLE_MEMBER,
            'icon' => 'fa-solid fa-user-group',
            'background' => 'bg-blue-50/50',
            'border' => 'border-blue-200',
            'title_color' => 'text-blue-900',
            'count_color' => 'bg-blue-200 text-blue-800',
        ],
        [
            'members' => $viewers,
            'role' => \App\Models\Project::ROLE_VIEWER,
            'icon' => 'fa-regular fa-eye',
            'background' => 'bg-yellow-50/70',
            'border' => 'border-yellow-200',
            'title_color' => 'text-yellow-700',
            'count_color' => 'bg-yellow-300 text-yellow-900',
        ],
    ];
@endphp

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    @foreach ($memberGroups as $memberGroup)
        @include('projects.partials.show.members._member-group', [
            'groupMembers' => $memberGroup['members'],
            'groupRole' => $memberGroup['role'],
            'groupTitle' => Str::plural($projectRoleLabels[$memberGroup['role']], 2),
            'iconClass' => $memberGroup['icon'],
            'backgroundClass' => $memberGroup['background'],
            'borderClass' => $memberGroup['border'],
            'titleClass' => $memberGroup['title_color'],
            'countClass' => $memberGroup['count_color'],
        ])
    @endforeach
</div>
