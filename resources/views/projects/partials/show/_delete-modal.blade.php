<div
    id="delete-project-modal"
    class="project-modal fixed inset-0 z-50 hidden overflow-y-auto"
>
    <div class="flex min-h-screen items-center justify-center px-4 py-8">
        <button
            type="button"
            class="fixed inset-0 bg-gray-900/60"
            onclick="closeProjectModal('delete-project-modal')"
            aria-label="Close modal"
        ></button>

        <div
            class="relative w-full max-w-lg overflow-hidden rounded-2xl
                bg-white text-left shadow-xl"
        >
            <div class="p-6">
                <div class="flex items-start gap-4">
                    <div
                        class="flex h-11 w-11 flex-shrink-0 items-center justify-center
                            rounded-full bg-red-100"
                    >
                        <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
                    </div>

                    <div>
                        <h3 class="text-lg font-bold text-gray-900">
                            Delete Project
                        </h3>

                        <p class="mt-2 text-sm text-gray-500">
                            Are you sure you want to delete
                            <span class="font-semibold text-gray-700">
                                {{ $project->name }}
                            </span>?
                            This action cannot be undone and all associated tasks
                            and project data will be removed.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-2 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                <button
                    type="button"
                    onclick="closeProjectModal('delete-project-modal')"
                    class="inline-flex justify-center rounded-lg border border-gray-300
                        bg-white px-4 py-2 text-sm font-medium text-gray-700
                        shadow-sm hover:bg-gray-50"
                >
                    Cancel
                </button>

                <form
                    method="POST"
                    action="{{ route('projects.destroy', $project->token) }}"
                >
                    @csrf
                    @method('DELETE')

                    <button
                        type="submit"
                        class="inline-flex w-full items-center justify-center gap-2 rounded-lg
                            bg-red-600 px-4 py-2 text-sm font-medium text-white
                            shadow-sm hover:bg-red-700 sm:w-auto"
                    >
                        <i class="fa-regular fa-trash-can"></i>
                        Delete Project
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
