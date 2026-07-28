@if ($canManageDiscussionThreads)
    <div
        id="create-discussion-modal"
        data-discussion-modal
        class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4"
    >
        <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
            <h3 class="text-lg font-semibold text-gray-900">New Discussion</h3>
            <p class="mt-1 text-sm text-gray-500">Create a Discussion Thread for {{ $project->name }}.</p>

            <form action="{{ route('discussion.threads.store', $project) }}" method="POST" class="mt-5 space-y-5">
                @csrf
                <input type="hidden" name="return_to" value="{{ $discussionContext ?? 'hub' }}">
                <label class="block">
                    <span class="mb-1.5 block text-sm font-medium text-gray-700">Discussion Title</span>
                    <input
                        name="name"
                        value="{{ old('name') }}"
                        required
                        maxlength="255"
                        class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                    >
                    @error('name')
                        <span class="mt-1 block text-xs text-red-600">{{ $message }}</span>
                    @enderror
                </label>
                <div class="flex justify-end gap-2">
                    <button type="button" data-discussion-modal-close class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                        Create Discussion
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
