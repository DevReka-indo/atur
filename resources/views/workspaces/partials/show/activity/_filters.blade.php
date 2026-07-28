@php
    $filters = [
        'all' => 'Semua aktivitas',
        'member' => 'Member',
        'invitation' => 'Invitation',
        'invite_link' => 'Invite link',
    ];
@endphp

<form method="GET" action="{{ route('workspaces.show', $workspace->token) }}"
    class="mb-6 grid gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm md:grid-cols-[minmax(0,1fr)_minmax(180px,240px)_auto]">
    <input type="hidden" name="tab" value="activity">

    <div>
        <label for="activity-search" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
            Search
        </label>
        <div class="relative">
            <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-3 text-sm text-gray-400"
                aria-hidden="true"></i>
            <input id="activity-search" name="search" type="search" value="{{ $activitySearch }}"
                placeholder="Cari actor atau aktivitas"
                class="w-full rounded-lg border border-gray-300 py-2.5 pl-9 pr-3 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
        </div>
    </div>

    <div>
        <label for="activity-filter" class="mb-1.5 block text-xs font-semibold uppercase tracking-wide text-gray-500">
            Tipe aktivitas
        </label>
        <select id="activity-filter" name="filter"
            class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm text-gray-800 focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-200">
            @foreach ($filters as $value => $label)
                <option value="{{ $value }}" @selected($activityFilter === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="flex items-end gap-2">
        <button type="submit"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-indigo-700">
            <i class="fa-solid fa-filter text-xs" aria-hidden="true"></i>
            Terapkan
        </button>
        @if ($activityFilter !== 'all' || $activitySearch !== '')
            <a href="{{ route('workspaces.show', ['token' => $workspace->token, 'tab' => 'activity']) }}"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50">
                Reset
            </a>
        @endif
    </div>
</form>
