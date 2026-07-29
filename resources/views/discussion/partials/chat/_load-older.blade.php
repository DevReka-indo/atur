<div id="discussion-load-older-container" class="{{ $hasMoreOlder ? '' : 'hidden' }} mb-4 text-center">
    <button
        id="discussion-load-older"
        type="button"
        class="rounded-full bg-white px-4 py-2 text-xs font-semibold text-indigo-600 shadow-sm hover:bg-indigo-50 disabled:cursor-wait disabled:text-gray-400"
    >
        <i class="fa-solid fa-clock-rotate-left mr-1"></i>
        <span data-load-older-label>Load Older Messages</span>
    </button>
    <p id="discussion-load-older-error" class="mt-2 hidden text-xs text-red-600"></p>
</div>
