<div class="flex flex-col-reverse gap-3 border-t border-gray-200 bg-gray-50/80 px-6 py-5 sm:flex-row sm:items-center sm:justify-between sm:px-8">
    <a href="{{ route('projects.index') }}"
        class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-300 bg-white px-6 py-3 text-sm font-medium text-gray-700 transition-all duration-200 hover:bg-gray-100">
        <i class="fa-solid fa-arrow-left"></i>
        Cancel
    </a>
    <button type="submit"
        class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-7 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700">
        <i class="fa-solid fa-check"></i>
        Create Project
    </button>
</div>
