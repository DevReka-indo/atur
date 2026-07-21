<div
    class="mb-4 flex items-center gap-1 overflow-x-auto rounded-lg bg-white p-1
        {{ $currentView !== 'list' ? 'invisible h-0 overflow-hidden p-0' : '' }}"
>
    @foreach ($statuses as $key => $label)
        <a
            href="{{ route('tasks.index', [
                'view' => $currentView,
                'status' => $key,
            ]) }}"
            class="whitespace-nowrap rounded-md px-4 py-1.5 text-sm transition-all
                {{ $currentStatus === $key
                    ? 'bg-[#ADE8F4] font-medium text-gray-900 shadow-sm'
                    : 'text-gray-500 hover:text-gray-700' }}"
        >
            {{ $label }}
        </a>
    @endforeach
</div>
