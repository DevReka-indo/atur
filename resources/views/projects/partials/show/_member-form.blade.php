@if ($canManageMembers)
    <form
        method="POST"
        action="{{ route('projects.members.store', $project->token) }}"
        class="mb-6 rounded-xl border border-gray-200 bg-gray-50 p-4"
    >
        @csrf

        <div class="grid grid-cols-1 items-end gap-3 md:grid-cols-12">
            <div class="md:col-span-5">
                <label
                    for="project_member_ids"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >
                    Select workspace member
                </label>

                <select
                    id="project_member_ids"
                    name="user_ids[]"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5
                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    required
                >
                    <option value="">Select workspace member</option>

                    @foreach ($availableMembers as $candidate)
                        <option value="{{ $candidate->id }}">
                            {{ $candidate->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="md:col-span-4">
                <label
                    for="project_member_role"
                    class="mb-1 block text-sm font-medium text-gray-700"
                >
                    Role
                </label>

                <select
                    id="project_member_role"
                    name="role"
                    class="w-full rounded-lg border border-gray-300 px-4 py-2.5
                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500"
                    required
                >
                    <option value="member">Member</option>
                    <option value="manager">Admin</option>
                    <option value="viewer">Viewer</option>
                </select>
            </div>

            <div class="md:col-span-3">
                <button
                    type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-lg bg-indigo-600
                        px-4 py-2.5 font-medium text-white transition-colors hover:bg-indigo-700"
                >
                    <i class="fa-solid fa-user-plus"></i>
                    Add Member
                </button>
            </div>
        </div>
    </form>
@endif
