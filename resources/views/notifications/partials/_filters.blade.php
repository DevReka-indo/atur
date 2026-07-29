<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <nav
        class="-mx-1 flex gap-2 overflow-x-auto px-1 pb-1"
        aria-label="Notification filters"
    >
        @foreach ($filterLabels as $filterKey => $filterLabel)
            @php($isActive = $filter === $filterKey)
            <a
                href="{{ route('notifications.index', ['filter' => $filterKey]) }}"
                class="inline-flex min-h-10 flex-none items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 {{ $isActive ? 'bg-blue-600 text-white shadow-sm' : 'border border-slate-200 bg-white text-slate-600 hover:border-blue-200 hover:bg-blue-50 hover:text-blue-700' }}"
                @if ($isActive) aria-current="page" @endif
            >
                {{ $filterLabel }}
                <span class="rounded-full px-1.5 py-0.5 text-xs tabular-nums {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-500' }}">
                    {{ $filterCounts[$filterKey] }}
                </span>
            </a>
        @endforeach
    </nav>

    @if ($notifications->count() > 0)
        <label class="inline-flex min-h-10 cursor-pointer select-none items-center gap-2 text-sm font-medium text-slate-600">
            <input
                type="checkbox"
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                aria-label="Select all notifications on this page"
                data-notification-select-all
            >
            Select page
        </label>
    @endif
</div>
