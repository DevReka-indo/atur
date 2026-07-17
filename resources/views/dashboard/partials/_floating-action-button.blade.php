<div id="fab-container" class="fixed bottom-20 right-6 z-50 select-none">
    <div
        id="fab-menu"
        class="absolute w-max flex flex-col items-end gap-2 opacity-0 pointer-events-none transition-all duration-300 scale-90 origin-bottom-right">
        <a href="{{ route('workspaces.create') }}"
            class="fab-item bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-2xl shadow-lg flex items-center gap-2.5 text-sm font-medium hover:bg-violet-50 hover:border-violet-300 hover:text-violet-700 transition-all duration-200 whitespace-nowrap opacity-0 -translate-x-2">
            <span class="w-7 h-7 bg-violet-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-folder-tree text-violet-600 text-xs"></i>
            </span>
            New Workspace
        </a>

        <a href="{{ route('projects.create') }}"
            class="fab-item bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-2xl shadow-lg flex items-center gap-2.5 text-sm font-medium hover:bg-violet-50 hover:border-violet-300 hover:text-violet-700 transition-all duration-200 whitespace-nowrap opacity-0 -translate-x-2">
            <span class="w-7 h-7 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-diagram-project text-indigo-600 text-xs"></i>
            </span>
            New Project
        </a>

        <a href="{{ route('tasks.create') }}"
            class="fab-item bg-white border border-gray-200 text-gray-700 px-4 py-2.5 rounded-2xl shadow-lg flex items-center gap-2.5 text-sm font-medium hover:bg-violet-50 hover:border-violet-300 hover:text-violet-700 transition-all duration-200 whitespace-nowrap opacity-0 -translate-x-2">
            <span class="w-7 h-7 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i class="fa-solid fa-list-check text-emerald-600 text-xs"></i>
            </span>
            New Task
        </a>
    </div>

    <button
        id="fab-btn"
        type="button"
        class="w-14 h-14 rounded-full shadow-2xl flex items-center justify-center cursor-grab active:cursor-grabbing transition-all duration-200 relative overflow-hidden bg-[#0096c7] border border-white/10">
        <span class="absolute inset-0 rounded-full bg-gradient-to-br from-white/20 to-transparent"></span>

        <span id="fab-icon" class="relative z-10 text-white text-xl transition-transform duration-300">
            <i class="fa-solid fa-plus"></i>
        </span>
    </button>
</div>
