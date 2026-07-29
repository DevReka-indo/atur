<div
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="workspace-chat-edit-title"
    data-chat-edit-modal
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 id="workspace-chat-edit-title" class="text-lg font-semibold text-gray-900">Edit Message</h2>
        <form class="mt-5 space-y-5" data-chat-edit-form>
            <div class="relative">
                <div
                    class="hidden min-h-28 max-h-64 w-full overflow-y-auto whitespace-pre-wrap break-words rounded-xl border border-gray-200 bg-white px-3 py-2 text-sm outline-none focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    role="textbox"
                    aria-label="Edit workspace chat message"
                    aria-multiline="true"
                    aria-disabled="true"
                    contenteditable="false"
                    data-chat-edit-input
                ></div>
                <span
                    class="pointer-events-none absolute left-3 top-2 text-sm text-gray-400"
                    aria-hidden="true"
                    data-chat-edit-placeholder
                >Edit message</span>
                <p class="text-sm text-amber-700" data-chat-edit-fallback>
                    Editor requires JavaScript.
                </p>
            </div>
            <div class="flex justify-end gap-2">
                <button
                    type="button"
                    class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700"
                    data-chat-edit-cancel
                >Cancel</button>
                <button
                    type="submit"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:cursor-not-allowed disabled:opacity-60"
                    disabled
                    data-chat-edit-submit
                >Save</button>
            </div>
        </form>
    </div>
</div>
