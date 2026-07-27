@php
    $projectRoleLabels = \App\Models\Project::roleLabels();
@endphp

<div id="project-tab-members"
    class="project-tab-content relative {{ $currentTab !== 'members' ? 'hidden' : '' }}"
    data-project-members>
    <div class="p-6">
        @include('projects.partials.show.members._header')
        @include('projects.partials.show.members._member-groups')
    </div>

    @if ($canManageMembers)
        @include('projects.partials.show.members._invite-modal')
    @endif
</div>
