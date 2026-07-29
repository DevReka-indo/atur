<footer class="flex-shrink-0 border-t border-gray-200 bg-gray-100 p-3">
    <form id="discussion-message-form" class="flex items-end gap-2">
        <div class="relative min-w-0 flex-1">
            <div
                id="discussion-message-input"
                role="textbox"
                aria-label="Project discussion message"
                aria-multiline="true"
                aria-autocomplete="list"
                aria-controls="project-discussion-mention-list"
                aria-expanded="false"
                aria-disabled="true"
                contenteditable="false"
                class="hidden min-h-10 max-h-36 w-full overflow-y-auto whitespace-pre-wrap break-words rounded-2xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm outline-none focus:ring-2 focus:ring-indigo-300"
            ></div>
            <span
                class="pointer-events-none absolute left-4 top-2.5 text-sm text-gray-400"
                id="discussion-message-placeholder"
                aria-hidden="true"
            >Write a message... Use @ to mention a member.</span>
            @include('discussion.partials.chat._mention-suggestions')
            <p class="px-3 text-xs text-amber-700" id="discussion-composer-fallback">
                Composer requires JavaScript. Sending is disabled until it is ready.
            </p>
        </div>
        <button
            type="submit"
            disabled
            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white shadow hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300"
            title="Send message"
        >
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>
    <p class="mt-1 px-3 text-xs text-gray-500">
        <i class="fa-solid fa-at mr-1 text-indigo-600" aria-hidden="true"></i>
        Mention only notifies the selected project members.
    </p>
    <p id="discussion-message-error" class="mt-1 hidden px-3 text-xs text-red-600"></p>
</footer>
