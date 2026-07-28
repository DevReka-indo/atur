<form
    method="GET"
    action="{{ route('discussion.index') }}"
    class="mb-6 grid gap-3 rounded-2xl border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-[1fr_auto_auto]"
>
    <label class="relative block">
        <span class="sr-only">Search projects or discussions</span>
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-3 text-sm text-gray-400"></i>
        <input
            type="search"
            name="search"
            value="{{ $search }}"
            placeholder="Search project or discussion..."
            class="w-full rounded-xl border-gray-200 py-2.5 pl-10 pr-3 text-sm focus:border-indigo-500 focus:ring-indigo-500"
        >
    </label>

    <label class="inline-flex items-center gap-2 rounded-xl border border-gray-200 px-4 py-2.5 text-sm text-gray-700">
        <input
            type="checkbox"
            name="unread"
            value="1"
            @checked($unreadOnly)
            class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
        >
        Unread only
    </label>

    <button
        type="submit"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700"
    >
        <i class="fa-solid fa-filter"></i>
        Apply
    </button>
</form>
