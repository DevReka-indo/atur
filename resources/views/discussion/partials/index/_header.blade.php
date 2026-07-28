<header class="mb-6 px-4 py-4 lg:py-6">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h1 class="text-3xl font-semibold text-slate-900 sm:text-4xl">
                Project Discussions
            </h1>
            <p class="mt-1 text-sm text-gray-500">
                Kelola pembahasan dan thread dari project yang Anda ikuti.
            </p>
        </div>

        <a
            href="{{ route('projects.index') }}"
            class="inline-flex items-center gap-2 text-sm font-medium text-indigo-600 hover:text-indigo-700"
        >
            <i class="fa-solid fa-diagram-project"></i>
            View Projects
        </a>
    </div>
</header>
