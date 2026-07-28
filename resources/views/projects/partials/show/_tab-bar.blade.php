<div class="border-b border-gray-200">
    <div class="flex flex-col gap-3 px-2 py-2 sm:flex-row sm:items-center sm:justify-between">
        <nav class="flex gap-1 overflow-x-auto">
            <button
                type="button"
                onclick="switchProjectTab('tasks')"
                data-project-tab="tasks"
                @if ($currentTab === 'tasks') aria-current="page" @endif
                class="project-tab-button whitespace-nowrap rounded-lg px-4 py-2.5 text-sm
                    font-medium transition-all
                    {{ $currentTab === 'tasks'
                        ? 'bg-[#ADE8F4] text-gray-700'
                        : 'text-gray-600 hover:text-gray-900' }}"
            >
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-list-check"></i>
                    Tasks
                </span>
            </button>

            @if ($canViewDiscussions ?? false)
                <a
                    href="{{ route('projects.show', ['token' => $project->token, 'tab' => 'discussions']) }}"
                    data-project-tab="discussions"
                    @if ($currentTab === 'discussions') aria-current="page" @endif
                    class="project-tab-button whitespace-nowrap rounded-lg px-4 py-2.5 text-sm
                        font-medium transition-all
                        {{ $currentTab === 'discussions'
                            ? 'bg-[#ADE8F4] text-gray-700'
                            : 'text-gray-600 hover:text-gray-900' }}"
                >
                    <span class="flex items-center gap-2">
                        <i class="fa-solid fa-comments"></i>
                        Discussions
                    </span>
                </a>
            @endif

            <button
                type="button"
                onclick="switchProjectTab('members')"
                data-project-tab="members"
                @if ($currentTab === 'members') aria-current="page" @endif
                class="project-tab-button whitespace-nowrap rounded-lg px-4 py-2.5 text-sm
                    font-medium transition-all
                    {{ $currentTab === 'members'
                        ? 'bg-[#ADE8F4] text-gray-700'
                        : 'text-gray-600 hover:text-gray-900' }}"
            >
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-user-group"></i>
                    Members
                </span>
            </button>

            <button
                type="button"
                onclick="switchProjectTab('chart')"
                data-project-tab="chart"
                @if ($currentTab === 'chart') aria-current="page" @endif
                class="project-tab-button whitespace-nowrap rounded-lg px-4 py-2.5 text-sm
                    font-medium transition-all
                    {{ $currentTab === 'chart'
                        ? 'bg-[#ADE8F4] text-gray-700'
                        : 'text-gray-600 hover:text-gray-900' }}"
            >
                <span class="flex items-center gap-2">
                    <i class="fa-solid fa-chart-line"></i>
                    Progress Chart
                </span>
            </button>
        </nav>

        <div
            id="project-task-actions"
            class="flex items-center gap-3
                {{ $currentTab !== 'tasks' ? 'hidden' : '' }}"
        >
            @include('projects.partials.show._view-switcher')

            @if ($canContribute)
                <a
                    href="{{ route('tasks.create', [
                        'project_token' => $project->token,
                    ]) }}"
                    class="group inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2
                        text-sm font-medium text-white shadow-md shadow-indigo-500/30
                        transition-all duration-300 hover:bg-indigo-700"
                >
                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                    Create Task
                </a>
            @endif
        </div>
    </div>
</div>
