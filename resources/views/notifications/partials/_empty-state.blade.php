<div class="px-6 py-16 text-center">
    <span class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-blue-50 text-2xl text-blue-600">
        <i class="fa-regular fa-bell" aria-hidden="true"></i>
    </span>

    @if ($filter === \App\Services\NotificationPresentationService::FILTER_ALL)
        <h3 class="mt-5 text-base font-semibold text-slate-900">No notifications yet</h3>
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
            You're all caught up. New project activity will appear here.
        </p>
    @else
        <h3 class="mt-5 text-base font-semibold text-slate-900">No notifications match this filter.</h3>
        <p class="mx-auto mt-2 max-w-md text-sm leading-6 text-slate-500">
            Try another category or return to all notifications.
        </p>
        <a
            href="{{ route('notifications.index', ['filter' => 'all']) }}"
            class="mt-5 inline-flex min-h-10 items-center rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        >
            Reset filter
        </a>
    @endif
</div>
