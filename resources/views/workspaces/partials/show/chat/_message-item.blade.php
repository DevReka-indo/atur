@php
    $sender = $message->user;
    $canManageMessage = $sender && (int) $message->user_id === (int) Auth::id();
    $avatar = $sender?->profile_photo
        ? asset('storage/'.$sender->profile_photo)
        : $sender?->avatar_url;
@endphp

<article class="group flex items-start gap-3" data-chat-message data-message-id="{{ $message->id }}"
    data-message-content="{{ $message->content }}">
    @if ($avatar)
        <img src="{{ $avatar }}" alt="{{ $sender?->name ?? 'Deleted user' }}"
            class="h-9 w-9 shrink-0 rounded-full object-cover">
    @else
        <span
            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-semibold text-indigo-700"
            aria-hidden="true">
            {{ str($sender?->name ?? '?')->substr(0, 1)->upper() }}
        </span>
    @endif

    <div class="min-w-0 flex-1">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="text-sm font-semibold text-gray-900" data-chat-sender>
                {{ $sender?->name ?? 'Deleted user' }}
            </span>
            <time class="text-xs text-gray-400" datetime="{{ $message->created_at->toIso8601String() }}">
                {{ $message->created_at->diffForHumans() }}
            </time>
            <span class="{{ $message->edited_at ? '' : 'hidden' }} text-xs italic text-gray-400"
                data-chat-edited>
                edited
            </span>
        </div>

        <p class="mt-1 whitespace-pre-wrap break-words text-sm leading-relaxed text-gray-700"
            data-chat-content>{{ $message->rendered_content }}</p>

        @if ($canManageMessage)
            <div class="mt-1 flex items-center gap-3 text-xs">
                <button type="button" class="font-medium text-indigo-600 hover:text-indigo-800"
                    data-chat-edit>
                    Edit
                </button>
                <button type="button" class="font-medium text-red-600 hover:text-red-800"
                    data-chat-delete>
                    Delete
                </button>
            </div>
        @endif
    </div>
</article>
