{{-- MODAL: Konfirmasi Remove Member --}}
<div id="remove-member-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm" onclick="closeRemoveMemberModal()"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center gap-3 mb-2">
            <div class="w-10 h-10 rounded-full bg-red-100 flex items-center justify-center text-red-600 flex-shrink-0">
                <i class="fa-solid fa-user-minus text-lg"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-gray-900">Remove Member</h3>
                <p class="text-sm text-gray-500" id="modal-member-name"></p>
            </div>
        </div>
        <div class="mt-4 bg-amber-50 border border-amber-200 rounded-xl p-4">
            <div class="flex gap-2">
                <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5 flex-shrink-0"></i>
                <p class="text-sm text-amber-700" id="modal-project-info"></p>
            </div>
        </div>
        <div class="mt-5 space-y-3">
            <button type="button" onclick="submitRemove('workspace-only')"
                class="w-full text-left flex items-start gap-3 px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-indigo-400 hover:bg-indigo-50 transition-all group">
                <div
                    class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-indigo-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-building-user text-gray-500 group-hover:text-indigo-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-indigo-700">Hapus dari Workspace
                        saja</p>
                    <p class="text-xs text-gray-500 mt-0.5">Member tetap ada di project yang sudah tergabung</p>
                </div>
            </button>
            <button type="button" onclick="submitRemove('cascade')"
                class="w-full text-left flex items-start gap-3 px-4 py-3 rounded-xl border-2 border-gray-200 hover:border-red-400 hover:bg-red-50 transition-all group">
                <div
                    class="w-8 h-8 rounded-lg bg-gray-100 group-hover:bg-red-100 flex items-center justify-center flex-shrink-0 mt-0.5">
                    <i class="fa-solid fa-trash-can text-gray-500 group-hover:text-red-600 text-sm"></i>
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800 group-hover:text-red-700">Hapus dari semua tempat</p>
                    <p class="text-xs text-gray-500 mt-0.5">Hapus dari workspace <strong>dan</strong> semua project
                        terkait</p>
                </div>
            </button>
        </div>
        <div class="mt-5 flex justify-end">
            <button type="button" onclick="closeRemoveMemberModal()"
                class="px-5 py-2.5 text-sm text-gray-700 font-medium rounded-xl border border-gray-300 hover:bg-gray-50 transition-colors">
                Batal
            </button>
        </div>
        <form id="form-remove" method="POST" class="hidden">
            @csrf @method('DELETE')
        </form>
    </div>
</div>
