@php
    $notification = $item['notification'];
    $presentation = $item['presentation'];
    $openUrl = $presentation['action_url']
        ? route('notifications.open', $notification)
        : null;
@endphp

<article
    class="group relative border-l-4 p-4 transition hover:bg-slate-50/80 sm:p-5 {{ $presentation['card_classes'] }} {{ $presentation['accent_classes'] }} {{ $openUrl ? 'cursor-pointer' : '' }}"
    data-notification-card
    data-notification-url="{{ $openUrl }}"
    data-notification-id="{{ $notification->id }}"
    @if ($openUrl)
        role="link"
        tabindex="0"
        aria-label="{{ $presentation['action_label'] }}: {{ $presentation['title'] }}"
    @endif
>
    <div class="flex items-start gap-3 sm:gap-4">
        <label class="flex min-h-11 min-w-8 cursor-pointer items-start justify-center pt-2">
            <span class="sr-only">Select notification: {{ $presentation['title'] }}</span>
            <input
                type="checkbox"
                value="{{ $notification->id }}"
                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                data-notification-checkbox
            >
        </label>

        <span
            class="flex h-11 w-11 flex-none items-center justify-center rounded-xl {{ $presentation['icon_classes'] }}"
            aria-label="{{ $presentation['severity_label'] }} notification"
            title="{{ $presentation['severity_label'] }}"
        >
            <i class="fa-solid {{ $presentation['icon'] }}" aria-hidden="true"></i>
        </span>

        <div class="min-w-0 flex-1">
            <div class="flex items-start gap-2">
                <h3 class="line-clamp-2 flex-1 text-sm leading-5 {{ $presentation['title_classes'] }}">
                    {{ $presentation['title'] }}
                </h3>
                @if ($presentation['is_unread'])
                    <span
                        class="mt-1.5 h-2.5 w-2.5 flex-none rounded-full bg-blue-600 ring-4 ring-blue-100"
                        aria-label="Unread notification"
                        title="Unread"
                    ></span>
                    <span class="sr-only">Unread</span>
                @endif
            </div>

            <p class="mt-1 line-clamp-3 text-sm leading-6 text-slate-600">
                {{ $presentation['description'] }}
            </p>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                @if ($presentation['context_label'])
                    <span class="inline-flex items-center gap-1.5">
                        <i class="fa-solid {{ $presentation['context_icon'] }}" aria-hidden="true"></i>
                        {{ $presentation['context_label'] }}
                    </span>
                @endif
                <time datetime="{{ $notification->created_at->toIso8601String() }}">
                    {{ $notification->created_at->diffForHumans() }}
                </time>
            </div>

            @if ($openUrl)
                <a
                    href="{{ $openUrl }}"
                    class="mt-3 inline-flex min-h-9 items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-blue-500"
                >
                    {{ $presentation['action_label'] }}
                    <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
                </a>
            @endif
        </div>

        <details class="relative flex-none" data-notification-menu>
            <summary
                class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-xl text-slate-500 transition hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                aria-label="Notification actions"
            >
                <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
            </summary>
            <div class="absolute right-0 z-20 mt-1 w-44 overflow-hidden rounded-xl border border-slate-200 bg-white py-1 shadow-xl">
                @if ($openUrl)
                    <a href="{{ $openUrl }}" class="flex items-center gap-2 px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                        <i class="fa-solid fa-arrow-up-right-from-square w-4 text-slate-400" aria-hidden="true"></i>
                        Open
                    </a>
                @endif

                @if ($presentation['is_unread'])
                    <form method="POST" action="{{ route('notifications.read', $notification) }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 hover:bg-slate-50">
                            <i class="fa-solid fa-check w-4 text-slate-400" aria-hidden="true"></i>
                            Mark as read
                        </button>
                    </form>
                @endif

                <form
                    method="POST"
                    action="{{ route('notifications.destroy', $notification) }}"
                    data-confirm="Delete this notification?"
                >
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-red-700 hover:bg-red-50">
                        <i class="fa-solid fa-trash-can w-4 text-red-500" aria-hidden="true"></i>
                        Delete
                    </button>
                </form>
            </div>
        </details>
    </div>
</article>
