<section aria-labelledby="workspace-activity-title">
    <div class="mb-5 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <h2 id="workspace-activity-title" class="text-xl font-bold text-gray-900">Workspace Activity Log</h2>
            <p class="mt-1 text-sm text-gray-500">
                Riwayat perubahan member, invitation, dan reusable invite link.
            </p>
        </div>
        <p class="text-sm text-gray-500">
            {{ number_format($activities->total()) }} aktivitas
        </p>
    </div>

    @include('workspaces.partials.show.activity._filters')
    @include('workspaces.partials.show.activity._activity-list')

    @if ($activities->hasPages())
        <div class="mt-6">
            {{ $activities->links() }}
        </div>
    @endif
</section>
