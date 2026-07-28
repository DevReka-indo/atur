<div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
    @forelse ($projects as $project)
        @include('discussion.partials.index._project-card', ['project' => $project])
    @empty
        <div class="col-span-full rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-14 text-center">
            <i class="fa-regular fa-comments text-4xl text-gray-300"></i>
            <h2 class="mt-3 text-base font-semibold text-gray-900">No Project Discussions found</h2>
            <p class="mt-1 text-sm text-gray-500">
                Only projects where you are a Project Admin or Member appear here.
            </p>
        </div>
    @endforelse
</div>
