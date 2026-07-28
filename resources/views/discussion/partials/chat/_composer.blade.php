<footer class="flex-shrink-0 border-t border-gray-200 bg-gray-100 p-3">
    <form id="discussion-message-form" class="flex items-center gap-2">
        <label class="sr-only" for="discussion-message-input">Message</label>
        <input
            id="discussion-message-input"
            name="content"
            maxlength="1000"
            autocomplete="off"
            placeholder="Write a message..."
            class="min-w-0 flex-1 rounded-full border-0 bg-white px-4 py-2.5 text-sm shadow-sm focus:ring-2 focus:ring-indigo-300"
        >
        <button
            type="submit"
            class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-600 text-white shadow hover:bg-indigo-700 disabled:cursor-not-allowed disabled:bg-gray-300"
            title="Send message"
        >
            <i class="fa-solid fa-paper-plane"></i>
        </button>
    </form>
    <p id="discussion-message-error" class="mt-1 hidden px-3 text-xs text-red-600"></p>
</footer>
