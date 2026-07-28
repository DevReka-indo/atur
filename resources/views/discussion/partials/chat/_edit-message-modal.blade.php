<div
    id="edit-message-modal"
    data-discussion-modal
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4"
>
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
        <h2 class="text-lg font-semibold text-gray-900">Edit Message</h2>
        <form id="edit-message-form" class="mt-5 space-y-5">
            <textarea
                id="edit-message-content"
                required
                maxlength="1000"
                rows="4"
                class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
            ></textarea>
            <div class="flex justify-end gap-2">
                <button type="button" data-discussion-modal-close class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700">
                    Cancel
                </button>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<div
    id="delete-message-modal"
    data-discussion-modal
    class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4"
>
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl">
        <i class="fa-solid fa-triangle-exclamation text-3xl text-red-500"></i>
        <h2 class="mt-3 text-lg font-semibold text-gray-900">Delete Message</h2>
        <p class="mt-2 text-sm text-gray-500">This message will be permanently deleted.</p>
        <div class="mt-6 flex justify-center gap-2">
            <button type="button" data-discussion-modal-close class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700">
                Cancel
            </button>
            <button id="delete-message-confirm" type="button" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">
                Delete
            </button>
        </div>
    </div>
</div>
