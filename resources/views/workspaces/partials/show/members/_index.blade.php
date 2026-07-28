@if ($canManageMembers)
    @include('workspaces.partials.members._pending-invitations', [
        'workspace' => $workspace,
    ])
@endif

<section class="mt-6" aria-label="Workspace members">
        {{-- Members List Cards --}}
        @php
            $owner = $workspace->members->first(fn($m) => $workspace->isOwner($m));
            $admins = $workspace->members
                ->filter(fn($m) => $m->pivot->role === 'admin' && !$workspace->isOwner($m))
                ->sortByDesc(fn($m) => $m->id === Auth::id());
            $regularMembers = $workspace->members
                ->filter(fn($m) => $m->pivot->role === 'member' && !$workspace->isOwner($m))
                ->sortByDesc(fn($m) => $m->id === Auth::id());
        @endphp

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- ===== Admins Group ===== --}}
            <div class="bg-purple-50/50 rounded-xl p-4 border-2 border-purple-200">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-bold text-purple-900 flex items-center gap-2">
                        <i class="fa-solid fa-shield-halved"></i> Workspace Admins
                    </h4>
                    <span class="bg-purple-200 text-purple-800 text-xs font-bold px-2 py-1 rounded-full">
                        {{ $admins->count() + ($owner ? 1 : 0) }}
                    </span>
                </div>
                <div class="space-y-2">

                    {{-- Owner (tidak ada tombol aksi) --}}
                    @if ($owner)
                        <div class="bg-white rounded-lg p-3 border border-amber-200 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">
                                @if ($owner->profile_photo)
                                    <img src="{{ asset('storage/' . $owner->profile_photo) }}"
                                        alt="{{ $owner->name }}"
                                        class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div
                                        class="w-10 h-10 rounded-full bg-gradient-to-br from-amber-400 to-orange-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($owner->name, 0, 1)) }}
                                    </div>
                                @endif
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $owner->name }}
                                    </p>
                                    <p class="text-gray-500 text-xs truncate">{{ $owner->email }}</p>
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <span
                                        class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-100 text-amber-700">
                                        <i class="fa-solid fa-crown text-[10px] mr-0.5"></i>
                                        {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_OWNER) }}
                                    </span>
                                    @if ($owner->id === Auth::id())
                                        <span
                                            class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Admin members dengan three dots --}}
                    @foreach ($admins as $member)
                        <div class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">

                                {{-- Avatar --}}
                                @if ($member->profile_photo)
                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                        alt="{{ $member->name }}"
                                        class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div
                                        class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif

                                {{-- Name & Email --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $member->name }}
                                    </p>
                                    <p class="text-gray-500 text-xs truncate">{{ $member->email }}</p>
                                </div>

                                {{-- You badge --}}
                                @if ($member->id === Auth::id())
                                    <span
                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                @endif

                                {{-- Three dots dropdown --}}
                                @if ($canManageMembers && $member->id !== Auth::id())
                                    <div class="relative flex-shrink-0">
                                        <button onclick="toggleMemberDropdown('{{ $member->id }}', 'adm')"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200
           text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors font-bold">
                                            ···
                                        </button>
                                        <div id="dd-adm-{{ $member->id }}"
                                            class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                            <button onclick="toggleSubRoles('sr-adm-{{ $member->id }}')"
                                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-purple-50 transition-colors">
                                                <span class="flex items-center gap-2">
                                                    <i class="fa-solid fa-user-pen w-4 text-purple-500 text-xs"></i>
                                                    Switch Role
                                                </span>
                                                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                                            </button>
                                            <div id="sr-adm-{{ $member->id }}"
                                                class="hidden border-t border-gray-100">
                                                @if ($member->pivot->role !== 'admin')
                                                    <form method="POST"
                                                        action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="role" value="admin">
                                                        <button type="submit"
                                                            class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_ADMIN) }}</button>
                                                    </form>
                                                @endif
                                                @if ($member->pivot->role !== 'member')
                                                    <form method="POST"
                                                        action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="role" value="member">
                                                        <button type="submit"
                                                            class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <button type="button"
                                                onclick="confirmRemoveMember(
    '{{ $member->name }}',
    '{{ route('workspaces.members.destroy', [$workspace->token, $member]) }}',
    '{{ route('workspaces.members.destroy.cascade', [$workspace->token, $member]) }}'
)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                <i class="fa-solid fa-user-minus w-4 text-xs"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            {{-- ===== Members Group ===== --}}
            <div class="bg-blue-50/50 rounded-xl p-4 border-2 border-blue-200">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-bold text-blue-900 flex items-center gap-2">
                        <i class="fa-solid fa-user-group"></i> Workspace Members
                    </h4>
                    <span
                        class="bg-blue-200 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">{{ $regularMembers->count() }}</span>
                </div>
                <div class="space-y-2">
                    @foreach ($regularMembers as $member)
                        <div class="bg-white rounded-lg p-3 border border-gray-200 hover:shadow-md transition-shadow">
                            <div class="flex items-center gap-2">

                                {{-- Avatar --}}
                                @if ($member->profile_photo)
                                    <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                        alt="{{ $member->name }}"
                                        class="w-10 h-10 rounded-full object-cover flex-shrink-0">
                                @else
                                    <div
                                        class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-cyan-400 text-white flex items-center justify-center font-bold text-sm flex-shrink-0">
                                        {{ strtoupper(substr($member->name, 0, 1)) }}
                                    </div>
                                @endif

                                {{-- Name & Email --}}
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-gray-900 text-sm truncate">{{ $member->name }}
                                    </p>
                                    <p class="text-gray-500 text-xs truncate">{{ $member->email }}</p>
                                </div>

                                {{-- You badge --}}
                                @if ($member->id === Auth::id())
                                    <span
                                        class="flex-shrink-0 text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                @endif

                                {{-- Three dots dropdown --}}
                                @if ($canManageMembers && $member->id !== Auth::id())
                                    <div class="relative flex-shrink-0">
                                        <button onclick="toggleMemberDropdown('{{ $member->id }}', 'mem')"
                                            class="w-7 h-7 flex items-center justify-center rounded-md border border-gray-200
           text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors font-bold">
                                            ···
                                        </button>
                                        <div id="dd-mem-{{ $member->id }}"
                                            class="hidden absolute right-0 top-full mt-1 w-44 bg-white rounded-xl shadow-lg border border-gray-100 z-50 overflow-hidden py-1">
                                            <button onclick="toggleSubRoles('sr-mem-{{ $member->id }}')"
                                                class="w-full flex items-center justify-between gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-purple-50 transition-colors">
                                                <span class="flex items-center gap-2">
                                                    <i class="fa-solid fa-user-pen w-4 text-purple-500 text-xs"></i>
                                                    Switch Role
                                                </span>
                                                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                                            </button>
                                            <div id="sr-mem-{{ $member->id }}"
                                                class="hidden border-t border-gray-100">
                                                @if ($member->pivot->role !== 'admin')
                                                    <form method="POST"
                                                        action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="role" value="admin">
                                                        <button type="submit"
                                                            class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_ADMIN) }}</button>
                                                    </form>
                                                @endif
                                                @if ($member->pivot->role !== 'member')
                                                    <form method="POST"
                                                        action="{{ route('workspaces.members.update', [$workspace->token, $member]) }}">
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="role" value="member">
                                                        <button type="submit"
                                                            class="w-full text-left px-3 py-2 pl-9 text-sm text-gray-600 hover:bg-purple-50 transition-colors">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}</button>
                                                    </form>
                                                @endif
                                            </div>
                                            <div class="border-t border-gray-100 my-1"></div>
                                            <button type="button"
                                                onclick="confirmRemoveMember(
    '{{ $member->name }}',
    '{{ route('workspaces.members.destroy', [$workspace->token, $member]) }}',
    '{{ route('workspaces.members.destroy.cascade', [$workspace->token, $member]) }}'
)"
                                                class="w-full flex items-center gap-2 px-3 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors">
                                                <i class="fa-solid fa-user-minus w-4 text-xs"></i> Remove
                                            </button>
                                        </div>
                                    </div>
                                @endif

                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        </div>
</section>
