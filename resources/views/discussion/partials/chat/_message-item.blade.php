@php
    $isOwnMessage = $message['sender']['id'] === auth()->id();
@endphp

<article
    data-discussion-message
    data-message-id="{{ $message['id'] }}"
    data-message-content="{{ $message['content'] }}"
    class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}"
>
    <div class="flex max-w-[85%] items-end gap-2 sm:max-w-[70%]">
        @if (! $isOwnMessage)
            @if ($message['sender']['avatar'])
                <img
                    src="{{ $message['sender']['avatar'] }}"
                    alt=""
                    class="h-8 w-8 flex-shrink-0 rounded-full object-cover"
                >
            @else
                <span class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-700">
                    {{ strtoupper(substr($message['sender']['name'], 0, 1)) }}
                </span>
            @endif
        @endif

        <div>
            @if (! $isOwnMessage)
                <p class="mb-1 px-2 text-xs font-semibold text-indigo-700">{{ $message['sender']['name'] }}</p>
            @endif

            <div class="group relative rounded-2xl px-4 py-2 shadow-sm {{ $isOwnMessage ? 'bg-green-100' : 'bg-white' }}">
            <p data-message-text class="whitespace-pre-wrap break-words text-sm text-gray-900">{{ $message['rendered_content'] }}</p>
                <div class="mt-1 flex items-center justify-end gap-2">
                    <span data-message-edited class="{{ $message['edited_at'] ? '' : 'hidden' }} text-[11px] text-gray-400">edited</span>
                    <time class="text-[11px] text-gray-400" datetime="{{ $message['created_at'] }}">{{ $message['created_at_human'] }}</time>
                    @if ($isOwnMessage)
                        <button type="button" data-message-edit class="text-[11px] font-medium text-gray-500 hover:text-indigo-600">
                            Edit
                        </button>
                        <button type="button" data-message-delete class="text-[11px] font-medium text-gray-500 hover:text-red-600">
                            Delete
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</article>
