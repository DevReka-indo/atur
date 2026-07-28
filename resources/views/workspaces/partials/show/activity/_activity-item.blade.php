@forelse ($activities as $activity)
    @php
        $presentation = $activity->presentation();
        $actorName = $activity->user?->name ?? 'System';
        $actorInitial = str($actorName)->substr(0, 1)->upper();
        $metadata = $activity->new_value ?? [];
        $summaryMetadata = array_filter([
            $metadata['role_label'] ?? null,
            isset($metadata['source']) ? str($metadata['source'])->replace('_', ' ')->title()->toString() : null,
            isset($metadata['status']) ? str($metadata['status'])->replace('_', ' ')->title()->toString() : null,
        ]);
    @endphp

    <article class="flex gap-4 border-b border-gray-100 px-5 py-4 last:border-b-0"
        data-activity-id="{{ $activity->id }}">
        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full {{ $presentation['bg'] }}">
            <i class="fa-solid {{ $presentation['icon'] }} {{ $presentation['color'] }} text-sm"
                aria-hidden="true"></i>
        </div>

        <div class="min-w-0 flex-1">
            <p class="text-sm leading-6 text-gray-700">
                <span class="font-semibold text-gray-900">{{ $actorName }}</span>
                <span class="text-gray-600">{{ $activity->displayDescription($canViewInvitationEmail) }}</span>
            </p>

            <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-gray-400">
                <span class="inline-flex items-center gap-1">
                    <i class="fa-regular fa-clock" aria-hidden="true"></i>
                    <time datetime="{{ $activity->created_at->toIso8601String() }}">
                        {{ $activity->created_at->diffForHumans() }}
                    </time>
                </span>

                @foreach ($summaryMetadata as $metadataValue)
                    <span class="rounded-full bg-gray-100 px-2 py-0.5 font-medium text-gray-600">
                        {{ $metadataValue }}
                    </span>
                @endforeach
            </div>
        </div>

        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-700"
            title="{{ $actorName }}">
            {{ $actorInitial }}
        </div>
    </article>
@empty
    @include('workspaces.partials.show.activity._empty-state')
@endforelse
