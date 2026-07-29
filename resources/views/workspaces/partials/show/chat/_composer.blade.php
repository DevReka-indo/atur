<form class="border-t border-gray-200 bg-gray-50 p-4 sm:px-6" data-chat-composer>
    @csrf
    <div class="flex items-end gap-3">
        <div class="relative flex-1">
            <div
                id="workspace-chat-content"
                class="hidden min-h-11 max-h-36 w-full overflow-y-auto whitespace-pre-wrap break-words rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                role="textbox"
                aria-label="Workspace chat message"
                aria-multiline="true"
                aria-autocomplete="list"
                aria-controls="workspace-chat-mention-list"
                aria-expanded="false"
                aria-disabled="true"
                contenteditable="false"
                data-chat-input
            ></div>
            <span
                class="pointer-events-none absolute left-3 top-2 text-sm text-gray-400"
                aria-hidden="true"
                data-chat-placeholder
            >Write a message... Use @ to mention a member.</span>
            @include('workspaces.partials.show.chat._mention-suggestions')
            <p class="text-sm text-amber-700" data-chat-composer-fallback>
                Composer requires JavaScript. Sending is disabled until it is ready.
            </p>
        </div>
        <button type="submit" disabled
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
