<header class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
    <div class="flex flex-col gap-5 xl:flex-row xl:items-center xl:justify-between">
        <div class="min-w-0">
            <div class="flex flex-wrap items-center gap-3">
                <h1 class="text-2xl font-bold tracking-tight text-slate-950 sm:text-3xl">Notifications</h1>
                <span
                    class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-1 text-xs font-semibold text-blue-700 ring-1 ring-inset ring-blue-200"
                    aria-label="{{ $unreadCount }} unread notifications"
                >
                    {{ $unreadCount }} unread
                </span>
            </div>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                Stay updated with project activity, discussions, and deadlines.
            </p>
        </div>

        <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
            <form
                method="POST"
                action="{{ route('notifications.destroySelected') }}"
                data-notification-bulk-form
                data-confirm="Delete the selected notifications?"
            >
                @csrf
                @method('DELETE')
                <button
                    type="submit"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-red-300 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 sm:w-auto"
                    aria-label="Delete selected notifications"
                    disabled
                    data-notification-bulk-submit
                >
                    <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
                    Delete Selected
                    <span
                        class="rounded-full bg-slate-100 px-2 py-0.5 text-xs tabular-nums text-slate-600"
                        data-notification-selected-count
                    >0</span>
                </button>
            </form>

            <form method="POST" action="{{ route('notifications.readAll') }}">
                @csrf
                <button
                    type="submit"
                    class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-slate-300 sm:w-auto"
                    aria-label="Mark all notifications as read"
                    @disabled($unreadCount === 0)
                >
                    <i class="fa-solid fa-check-double" aria-hidden="true"></i>
                    Mark All as Read
                </button>
            </form>
        </div>
    </div>
</header>
