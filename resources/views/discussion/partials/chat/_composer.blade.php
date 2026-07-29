<footer class="flex-shrink-0 border-t border-gray-200 bg-gray-100 p-3">
    <form id="discussion-message-form" class="flex items-end gap-2">
        <label class="sr-only" for="discussion-message-input">Message</label>
        <div class="relative min-w-0 flex-1">
            <textarea
                id="discussion-message-input"
                name="content"
                rows="2"
                maxlength="1000"
                autocomplete="off"
                placeholder="Write a message... Use @ to mention a member."
                aria-autocomplete="list"
                aria-controls="project-discussion-mention-list"
                aria-expanded="false"
                class="min-h-10 w-full resize-none rounded-2xl border-0 bg-white px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-300"
            ></textarea>
            @include('discussion.partials.chat._mention-suggestions')
            <div
                id="discussion-mention-preview"
                class="mt-2 hidden flex flex-wrap gap-1.5"
                aria-label="Selected mentions"
            ></div>
        </div>
        <button
            type="submit"
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
