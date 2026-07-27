@php
    $oldSelectedMemberId = (string) old('user_ids.0', '');
    $hasInviteErrors = $errors->has('user_ids') || $errors->has('user_ids.*') || $errors->has('role');
@endphp

<div class="fixed inset-0 z-50 hidden"
    data-member-invite-modal
    data-open-on-error="{{ $hasInviteErrors ? 'true' : 'false' }}"
    aria-hidden="true">
    <div class="absolute inset-0 bg-slate-950/60 backdrop-blur-sm" data-member-invite-backdrop></div>

    <div class="relative flex min-h-full items-end justify-center p-0 sm:items-center sm:p-6">
        <form method="POST"
            action="{{ route('projects.members.store', $project->token) }}"
            class="flex max-h-[92vh] w-full max-w-2xl flex-col overflow-hidden rounded-t-3xl bg-white shadow-2xl sm:rounded-3xl"
            role="dialog"
            aria-modal="true"
            aria-labelledby="invite-project-member-title"
            aria-describedby="invite-project-member-description"
            tabindex="-1"
            data-member-invite-dialog>
            @csrf

            <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
                <div>
                    <h2 id="invite-project-member-title" class="text-xl font-bold text-slate-900">
                        Invite Project Member
                    </h2>
                    <p id="invite-project-member-description" class="mt-1 text-sm leading-6 text-slate-600">
                        Select an existing member from this workspace and assign project access.
                    </p>
                </div>
                <button type="button"
                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-slate-800"
                    aria-label="Close member picker"
                    data-close-member-invite>
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </header>

            <div class="min-h-0 flex-1 space-y-5 overflow-y-auto px-5 py-5 sm:px-6">
                @if ($hasInviteErrors)
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700" role="alert">
                        @error('user_ids')
                            <p>{{ $message }}</p>
                        @enderror
                        @error('user_ids.*')
                            <p>{{ $message }}</p>
                        @enderror
                        @error('role')
                            <p>{{ $message }}</p>
                        @enderror
                    </div>
                @endif

                <div>
                    <label for="project-member-search" class="mb-2 block text-sm font-semibold text-slate-800">
                        Workspace Member
                    </label>
                    <div class="relative">
                        <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="search" id="project-member-search"
                            class="w-full rounded-xl border-slate-300 py-2.5 pl-10 pr-4 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                            placeholder="Search by name or email..."
                            autocomplete="off"
                            data-member-search>
                    </div>

                    <input type="hidden" name="user_ids[]" value="{{ $oldSelectedMemberId }}" data-selected-member-id>

                    <div class="mt-3 space-y-2" data-member-candidates>
                        @forelse ($availableMembers as $candidate)
                            <button type="button"
                                class="flex w-full items-center gap-3 rounded-xl border-2 border-slate-200 bg-white p-3 text-left transition hover:border-indigo-300"
                                data-member-candidate
                                data-member-id="{{ $candidate->id }}"
                                data-search-text="{{ Str::lower($candidate->name.' '.$candidate->email) }}"
                                aria-pressed="false">
                                @if ($candidate->profile_photo)
                                    <img src="{{ asset('storage/'.$candidate->profile_photo) }}" alt="{{ $candidate->name }}"
                                        class="h-10 w-10 shrink-0 rounded-full object-cover">
                                @else
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-indigo-400 to-purple-400 text-sm font-bold text-white">
                                        {{ strtoupper(substr($candidate->name, 0, 1)) }}
                                    </span>
                                @endif
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate text-sm font-semibold text-slate-900">{{ $candidate->name }}</span>
                                    <span class="block truncate text-xs text-slate-500">{{ $candidate->email }}</span>
                                </span>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                    Workspace {{ Str::headline($candidate->pivot->role) }}
                                </span>
                                <span class="hidden h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-600 text-xs text-white"
                                    data-member-selected-indicator>
                                    <i class="fa-solid fa-check"></i>
                                </span>
                            </button>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
                                data-no-member-candidates>
                                <i class="fa-solid fa-user-check text-2xl text-slate-300"></i>
                                <p class="mt-3 font-semibold text-slate-800">No workspace members available</p>
                                <p class="mt-1 text-sm text-slate-500">Everyone in this workspace is already in the project.</p>
                            </div>
                        @endforelse
                    </div>

                    <div class="mt-3 hidden rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center"
                        data-member-no-results>
                        <i class="fa-solid fa-magnifying-glass text-2xl text-slate-300"></i>
                        <p class="mt-3 font-semibold text-slate-800">No members found</p>
                        <p class="mt-1 text-sm text-slate-500">Try another name or email.</p>
                    </div>
                </div>

                <div>
                    <label for="project_member_role" class="mb-2 block text-sm font-semibold text-slate-800">
                        Project Role
                    </label>
                    <select id="project_member_role" name="role" required
                        class="w-full rounded-xl border-slate-300 py-2.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @foreach ($projectRoleLabels as $roleValue => $roleLabel)
                            <option value="{{ $roleValue }}" @selected(old('role', \App\Models\Project::ROLE_MEMBER) === $roleValue)>
                                {{ $roleLabel }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <footer class="flex flex-col-reverse gap-2 border-t border-slate-200 bg-white px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
                <button type="button"
                    class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                    data-close-member-invite>
                    Cancel
                </button>
                <button type="submit"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                    data-submit-member-invite
                    @disabled($oldSelectedMemberId === '')>
                    <i class="fa-solid fa-user-plus"></i>
                    Add Member
                </button>
            </footer>
        </form>
    </div>
</div>
