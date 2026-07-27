<div class="flex flex-col gap-3 rounded-xl border border-gray-200 bg-white p-4 sm:flex-row sm:items-center"
    data-pending-invitation="{{ $invitation->id }}">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-amber-100 text-amber-700">
            <i class="fa-solid fa-envelope"></i>
        </div>
        <div class="min-w-0">
            <p class="truncate text-sm font-semibold text-gray-900">{{ $invitation->email }}</p>
            <p class="text-xs text-gray-500">
                {{ \App\Models\Workspace::roleLabel($invitation->role) }}
                <span aria-hidden="true">·</span>
                Diundang {{ $invitation->created_at->diffForHumans() }}
            </p>
            <p class="text-xs text-gray-400">
                Berlaku hingga {{ $invitation->expires_at->format('d M Y, H:i') }}
                @if ($invitation->inviter)
                    oleh {{ $invitation->inviter->name }}
                @endif
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">Pending</span>
        <form method="POST"
            action="{{ route('workspaces.invitations.resend', [$workspace->token, $invitation]) }}">
            @csrf
            <button type="submit"
                class="rounded-lg border border-gray-300 px-3 py-2 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-rotate-right mr-1"></i> Resend
            </button>
        </form>
        <form method="POST"
            action="{{ route('workspaces.invitations.revoke', [$workspace->token, $invitation]) }}">
            @csrf
            @method('DELETE')
            <button type="submit"
                class="rounded-lg border border-red-200 px-3 py-2 text-xs font-semibold text-red-600 hover:bg-red-50">
                <i class="fa-solid fa-ban mr-1"></i> Revoke
            </button>
        </form>
    </div>
</div>
