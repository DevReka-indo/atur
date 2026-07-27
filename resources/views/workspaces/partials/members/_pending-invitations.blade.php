<section class="mt-6 rounded-xl border border-gray-200 bg-gray-50/70 p-4"
    aria-labelledby="pending-invitations-title"
    data-pending-invitations>
    <div class="mb-3 flex items-center justify-between gap-3">
        <div>
            <h3 id="pending-invitations-title" class="font-bold text-gray-900">Pending Invitations</h3>
            <p class="text-xs text-gray-500">Undangan email yang belum diterima.</p>
        </div>
        <span class="rounded-full bg-gray-200 px-2.5 py-1 text-xs font-bold text-gray-700">
            {{ $workspace->invitations->count() }}
        </span>
    </div>

    @forelse ($workspace->invitations as $invitation)
        @include('workspaces.partials.members._pending-invitation-item', [
            'workspace' => $workspace,
            'invitation' => $invitation,
        ])
    @empty
        <div class="rounded-xl border border-dashed border-gray-300 bg-white px-4 py-6 text-center text-sm text-gray-500">
            Tidak ada undangan aktif.
        </div>
    @endforelse
</section>
