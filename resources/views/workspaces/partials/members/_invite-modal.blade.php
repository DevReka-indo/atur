<div class="fixed inset-0 z-50 hidden items-center justify-center p-4"
    data-workspace-invite-modal
    data-search-url="{{ route('workspaces.members.candidates', $workspace->token) }}"
    role="dialog"
    aria-modal="true"
    aria-labelledby="workspace-invite-title">
    <button type="button"
        class="absolute inset-0 bg-gray-900/50 backdrop-blur-sm"
        data-close-workspace-invite
        aria-label="Tutup modal invitation"></button>

    <div class="relative max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white shadow-2xl">
        <div class="flex items-start justify-between gap-4 border-b border-gray-100 px-6 py-5">
            <div>
                <h2 id="workspace-invite-title" class="text-lg font-bold text-gray-900">Invite Workspace Member</h2>
                <p class="mt-1 text-sm text-gray-500">
                    Cari pengguna yang sudah terdaftar atau masukkan alamat email baru.
                </p>
            </div>
            <button type="button"
                data-close-workspace-invite
                class="flex h-9 w-9 items-center justify-center rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600"
                aria-label="Tutup modal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST"
            action="{{ route('workspaces.invitations.store', $workspace->token) }}"
            data-workspace-invite-form>
            @csrf
            <input type="hidden" name="user_id" data-workspace-invite-user-id>
            <input type="hidden" name="email" data-workspace-invite-email>

            <div class="space-y-5 px-6 py-5">
                <div class="space-y-2">
                    <label for="workspace-invite-search" class="block text-sm font-semibold text-gray-700">
                        Cari pengguna atau masukkan email
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass absolute left-3.5 top-3.5 text-sm text-gray-400"></i>
                        <input id="workspace-invite-search"
                            type="search"
                            autocomplete="off"
                            data-workspace-invite-search
                            class="w-full rounded-xl border border-gray-300 py-3 pl-10 pr-4 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                            placeholder="Nama atau email"
                            aria-controls="workspace-invite-results">
                    </div>
                    @include('workspaces.partials.members._invite-search-results')
                    @error('candidate')
                        <p class="text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="workspace-invite-role" class="block text-sm font-semibold text-gray-700">Role</label>
                    <select id="workspace-invite-role"
                        name="role"
                        class="mt-2 w-full rounded-xl border border-gray-300 px-4 py-3 text-sm focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">
                        @foreach (\App\Models\Workspace::INVITABLE_ROLE_LABELS as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-link mt-0.5 text-indigo-500"></i>
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-semibold text-indigo-900">Reusable invite link</p>
                            <p class="mt-1 text-xs text-indigo-700">
                                Role default: Workspace Member.
                                @if ($workspace->hasActiveInviteLink())
                                    Berlaku hingga {{ $workspace->invite_token_expires_at->format('d M Y, H:i') }}.
                                @else
                                    Link belum aktif atau sudah kedaluwarsa.
                                @endif
                            </p>
                            @if ($workspace->hasActiveInviteLink())
                                <div class="mt-3 flex gap-2">
                                    <input type="text"
                                        readonly
                                        value="{{ $workspace->invite_link }}"
                                        data-workspace-invite-link
                                        class="min-w-0 flex-1 rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs text-gray-600">
                                    <button type="button"
                                        data-copy-workspace-invite-link
                                        class="rounded-lg border border-indigo-200 bg-white px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100">
                                        <i class="fa-regular fa-copy mr-1"></i> Copy
                                    </button>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col-reverse gap-3 border-t border-gray-100 bg-gray-50 px-6 py-4 sm:flex-row sm:justify-end">
                <button type="button"
                    data-close-workspace-invite
                    class="rounded-xl border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit"
                    data-workspace-invite-submit
                    disabled
                    class="rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50">
                    Add Member
                </button>
            </div>
        </form>

        <div class="flex flex-wrap gap-2 border-t border-gray-100 px-6 py-4">
            <form method="POST"
                action="{{ $workspace->hasActiveInviteLink()
                    ? route('workspaces.invite.reset', $workspace->token)
                    : route('workspaces.invite.generate', $workspace->token) }}">
                @csrf
                <button type="submit" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                    <i class="fa-solid fa-rotate mr-1"></i>
                    {{ $workspace->hasActiveInviteLink() ? 'Regenerate link' : 'Generate link' }}
                </button>
            </form>
            @if ($workspace->hasActiveInviteLink())
                <form method="POST" action="{{ route('workspaces.invite.revoke', $workspace->token) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800">
                        <i class="fa-solid fa-ban mr-1"></i> Disable link
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
