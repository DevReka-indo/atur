<form class="border-t border-gray-200 bg-gray-50 p-4 sm:px-6" data-chat-composer>
    @csrf
    <label for="workspace-chat-content" class="sr-only">Message</label>
    <div class="flex items-end gap-3">
        <textarea id="workspace-chat-content" name="content" rows="2" maxlength="1000" required
            class="min-h-11 flex-1 resize-none rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder="Write a message..." data-chat-input></textarea>
        <button type="submit"
            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            data-chat-submit>
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            <span data-chat-submit-label>Send</span>
        </button>
    </div>
    <p class="mt-2 hidden text-sm text-red-600" role="alert" data-chat-error></p>
</form>
