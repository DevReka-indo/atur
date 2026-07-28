@if ($isOwner)
    <div id="delete-workspace-modal" class="fixed inset-0 z-50 hidden items-center justify-center p-4">
        <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
            onclick="closeModal('delete-workspace-modal')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6 transform transition-all">
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600">
                    <i class="fa-solid fa-triangle-exclamation text-lg"></i>
                </div>
                <h3 class="text-lg font-bold text-gray-900">Confirm Delete</h3>
            </div>
            <p class="text-gray-600 mb-6">
                Are you sure you want to delete
                <strong class="text-gray-900">{{ $workspace->name }}</strong>?
                This action cannot be undone and all associated projects and data will be permanently removed.
            </p>
            <div class="flex flex-col-reverse sm:flex-row gap-3 sm:justify-end">
                <button onclick="closeModal('delete-workspace-modal')"
                    class="px-5 py-2.5 text-gray-700 font-medium rounded-xl border border-gray-300 bg-white hover:bg-gray-50 transition-colors">
                    Cancel
                </button>
                <form method="POST" action="{{ route('workspaces.destroy', $workspace->token) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                        class="w-full sm:w-auto inline-flex items-center justify-center px-5 py-2.5
                    rounded-xl font-medium text-sm text-white bg-red-600 hover:bg-red-700 transition-all">
                        <i class="fa-solid fa-trash mr-2"></i>
                        Yes, Delete Workspace
                    </button>
                </form>
            </div>
        </div>
    </div>
@endif
