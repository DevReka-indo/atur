@php
    $isOwnMessage = $message->user_id === auth()->id();
@endphp

<article
    data-discussion-message
    data-message-id="{{ $message->id }}"
    data-message-content="{{ $message->content }}"
    class="flex {{ $isOwnMessage ? 'justify-end' : 'justify-start' }}"
>
    <div class="max-w-[80%] sm:max-w-[65%]">
        @if (! $isOwnMessage)
            <p class="mb-1 px-2 text-xs font-semibold text-indigo-700">{{ $message->user->name }}</p>
        @endif

        <div class="group relative rounded-2xl px-4 py-2 shadow-sm {{ $isOwnMessage ? 'bg-green-100' : 'bg-white' }}">
            <p data-message-text class="whitespace-pre-wrap break-words text-sm text-gray-900">{{ $message->content }}</p>
            <div class="mt-1 flex items-center justify-end gap-2">
                <time class="text-[11px] text-gray-400">{{ $message->created_at->format('H:i') }}</time>
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
</article>
