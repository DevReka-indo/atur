<form class="border-t border-gray-200 bg-gray-50 p-4 sm:px-6" data-chat-composer>
    @csrf
    <label for="workspace-chat-content" class="sr-only">Message</label>
    <div class="flex items-end gap-3">
        <div class="relative flex-1">
            <textarea id="workspace-chat-content" name="content" rows="2" maxlength="1000" required
                class="min-h-11 w-full resize-none rounded-xl border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                placeholder="Write a message... Use @ to mention a member." aria-autocomplete="list"
                aria-controls="workspace-chat-mention-list" aria-expanded="false" data-chat-input></textarea>
            @include('workspaces.partials.show.chat._mention-suggestions')
            <div class="mt-2 hidden flex flex-wrap gap-1.5" aria-label="Selected mentions"
                data-chat-mention-preview></div>
        </div>
        <button type="submit"
            class="inline-flex h-11 items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 text-sm font-medium text-white transition-colors hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
            data-chat-submit>
            <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
            <span data-chat-submit-label>Send</span>
        </button>
    </div>
    <p class="mt-2 text-xs text-gray-500">
        <i class="fa-solid fa-at mr-1 text-sky-600" aria-hidden="true"></i>
        Mention hanya mengirim notifikasi kepada anggota yang dipilih.
    </p>
    <p class="mt-2 hidden text-sm text-red-600" role="alert" data-chat-error></p>
</form>
