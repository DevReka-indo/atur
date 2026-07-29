<div
    id="edit-message-modal"
    data-discussion-modal
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-lg font-semibold text-gray-900">Edit Message</h2>
        <form id="edit-message-form" class="mt-5 space-y-5">
            <div class="relative">
            <div
                id="edit-message-content"
                class="hidden min-h-28 max-h-64 w-full overflow-y-auto whitespace-pre-wrap break-words rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                role="textbox"
                aria-label="Edit project discussion message"
                aria-multiline="true"
                aria-disabled="true"
                contenteditable="false"
            ></div>
            <span
                id="edit-message-placeholder"
                class="pointer-events-none absolute left-3 top-2 text-sm text-gray-400"
                aria-hidden="true"
            >Edit message</span>
            <p id="edit-message-fallback" class="text-sm text-amber-700">
                Editor requires JavaScript.
            </p>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" data-discussion-modal-close class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700">
                    Cancel
                </button>
                <button type="submit" disabled class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>
