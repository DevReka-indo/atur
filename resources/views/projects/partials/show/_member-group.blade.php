<div class="rounded-xl border-2 {{ $borderClass }} {{ $backgroundClass }} p-4">
    <div class="mb-4 flex items-center justify-between">
        <h4 class="flex items-center gap-2 font-bold {{ $titleClass }}">
            <i class="{{ $iconClass }}"></i>
            {{ $title }}
        </h4>

        <span class="rounded-full px-2 py-1 text-xs font-bold {{ $countClass }}">
            {{ $groupMembers->count() }}
        </span>
    </div>

    <div class="space-y-2">
        @forelse ($groupMembers as $member)
            @include('projects.partials.show._member-card', [
                'member' => $member,
                'groupKey' => $groupKey,
            ])
        @empty
            <div class="rounded-lg border border-dashed border-gray-300 bg-white/70 p-6 text-center">
                <i class="fa-solid fa-user-slash text-gray-300"></i>

                <p class="mt-2 text-sm text-gray-500">
                    No {{ strtolower($title) }} assigned.
                </p>
            </div>
        @endforelse
    </div>
</div>
