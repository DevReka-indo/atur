<div
    id="taskInfoModal"
    class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 px-4"
>
    <div
        class="max-h-[80vh] w-full max-w-xl overflow-y-auto rounded-2xl
            bg-white p-6 shadow-xl"
    >
        <div class="flex items-start justify-between gap-4">
            <div>
                <h2 class="text-xl font-semibold text-slate-900">
                    About My Tasks
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    This page shows work assigned to you across all projects.
                </p>
            </div>

            <button
                type="button"
                onclick="closeTaskInfoModal()"
                class="flex h-8 w-8 items-center justify-center rounded-lg
                    text-slate-400 hover:bg-slate-100 hover:text-slate-600"
                aria-label="Close"
            >
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="mt-5 space-y-4 text-sm text-slate-600">
            <div class="flex gap-3 border-t pt-4">
                <i class="fa-solid fa-user-check mt-1 text-indigo-500"></i>
                <p>
                    <span class="font-semibold text-slate-800">Personal scope</span>
                    — task yang ditugaskan kepada Anda akan ditampilkan di sini.
                </p>
            </div>

            <div class="flex gap-3 border-t pt-4">
                <i class="fa-solid fa-diagram-project mt-1 text-indigo-500"></i>
                <p>
                    <span class="font-semibold text-slate-800">Hierarchy context</span>
                    — parent dapat ditampilkan sebagai konteks meskipun Anda hanya menjadi PIC child.
                </p>
            </div>

            <div class="flex gap-3 border-t pt-4">
                <i class="fa-solid fa-layer-group mt-1 text-indigo-500"></i>
                <p>
                    <span class="font-semibold text-slate-800">Three views</span>
                    — gunakan List, Gantt, atau Kanban sesuai kebutuhan.
                </p>
            </div>

            <div class="flex gap-3 border-t pt-4">
                <i class="fa-solid fa-table-columns mt-1 text-indigo-500"></i>
                <p>
                    <span class="font-semibold text-slate-800">Kanban</span>
                    — hanya executable leaf task yang ditampilkan dan dapat dipindahkan.
                </p>
            </div>

            <div class="flex gap-3 border-t pt-4">
                <i class="fa-solid fa-arrow-down-short-wide mt-1 text-indigo-500"></i>
                <p>
                    <span class="font-semibold text-slate-800">Smart sorting</span>
                    — task aktif dan mendekati due date diprioritaskan.
                </p>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button
                type="button"
                onclick="closeTaskInfoModal()"
                class="rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700"
            >
                Done
            </button>
        </div>
    </div>
</div>
