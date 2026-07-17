<div class="px-4 sm:px-6 lg:px-8 py-6 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl sm:text-3xl font-semibold text-gray-900 tracking-tight">
                Welcome, {{ Auth::user()->name }}
            </h1>

            <p class="mt-2 text-sm sm:text-base text-gray-500 max-w-2xl">
                Track your project progress, upcoming deadlines, and team activity at a glance.
            </p>
        </div>

        <div class="flex-shrink-0">
            <span
                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white border border-gray-200 rounded-lg shadow-sm text-sm font-medium text-gray-600">
                <i class="fa-regular fa-calendar text-gray-400"></i>
                {{ now()->format('l, d F Y') }}
            </span>
        </div>
    </div>
</div>
