@php
    $threads = $discussionThreads ?? $threads;
    $canManageDiscussionThreads = $canManageDiscussionThreads ?? $project->canManageDiscussionThreads(auth()->user());
@endphp

@php
    $isProjectTab = ($discussionContext ?? 'hub') === 'project';
@endphp

<section id="{{ $isProjectTab ? 'project-tab-discussions' : 'project-discussions' }}" data-project-discussions
    data-unread-url="{{ route('discussion.unread', $project) }}"
    class="{{ $isProjectTab ? 'project-tab-content relative space-y-6 px-5 py-6 sm:px-7 sm:py-7' : 'space-y-6' }}">
    <div class="space-y-5">
        @include('discussion.partials.project._header')

        @include('discussion.partials.project._thread-list')
    </div>
    @include('discussion.partials.project._create-thread-modal')

    @if ($canManageDiscussionThreads)
        <div id="rename-discussion-modal" data-discussion-modal
            class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                <h3 class="text-lg font-semibold text-gray-900">Rename Discussion</h3>
                <form id="rename-discussion-form" method="POST" class="mt-5 space-y-5">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="return_to" value="{{ $discussionContext ?? 'hub' }}">
                    <label class="block">
                        <span class="mb-1.5 block text-sm font-medium text-gray-700">Discussion Title</span>
                        <input id="rename-discussion-title" name="name" required maxlength="255"
                            class="w-full rounded-xl border-gray-200 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </label>
                    <div class="flex justify-end gap-2">
                        <button type="button" data-discussion-modal-close
                            class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700">
                            Cancel
                        </button>
                        <button type="submit"
                            class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">
                            Save
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="delete-discussion-modal" data-discussion-modal
            class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-900/50 p-4">
            <div class="w-full max-w-md rounded-2xl bg-white p-6 text-center shadow-2xl">
                <i class="fa-solid fa-triangle-exclamation text-3xl text-red-500"></i>
                <h3 class="mt-3 text-lg font-semibold text-gray-900">Delete Discussion</h3>
                <p class="mt-2 text-sm text-gray-500">The thread and all its messages will be permanently deleted.</p>
                <form id="delete-discussion-form" method="POST" class="mt-6 flex justify-center gap-2">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="return_to" value="{{ $discussionContext ?? 'hub' }}">
                    <button type="button" data-discussion-modal-close
                        class="rounded-lg bg-gray-100 px-4 py-2 text-sm text-gray-700">
                        Cancel
                    </button>
                    <button type="submit" class="rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    @endif
</section>
