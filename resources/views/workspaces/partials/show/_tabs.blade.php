@php
    $tabs = [
        'overview' => ['label' => 'Overview', 'icon' => 'fa-diagram-project'],
        'members' => ['label' => 'Members', 'icon' => 'fa-user-group'],
        'activity' => ['label' => 'Activity Log', 'icon' => 'fa-clock-rotate-left'],
    ];
@endphp

<nav class="inline-flex max-w-full gap-1 overflow-x-auto rounded-xl bg-white p-1.5 shadow-sm"
    aria-label="Workspace sections">
    @foreach ($tabs as $tab => $config)
        <a href="{{ route('workspaces.show', ['token' => $workspace->token, 'tab' => $tab]) }}"
            @if ($activeTab === $tab) aria-current="page" @endif
            class="inline-flex shrink-0 items-center gap-2 rounded-lg px-4 py-2.5 text-sm transition-all duration-200
                {{ $activeTab === $tab
                    ? 'bg-[#ADE8F4] font-medium text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700' }}">
            <i class="fa-solid {{ $config['icon'] }} w-5 text-center text-sm" aria-hidden="true"></i>
            <span>{{ $config['label'] }}</span>
        </a>
    @endforeach
</nav>
