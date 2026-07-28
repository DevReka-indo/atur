<div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm" data-workspace-chat
    data-index-url="{{ route('workspace-chat.messages.index', $workspace) }}"
    data-store-url="{{ route('workspace-chat.messages.store', $workspace) }}"
    data-read-url="{{ route('workspace-chat.read', $workspace) }}"
    data-mentions-url="{{ route('workspace-chat.mentions', $workspace) }}"
    data-update-url-template="{{ route('workspace-chat.messages.update', [$workspace, '__MESSAGE_ID__']) }}"
    data-delete-url-template="{{ route('workspace-chat.messages.destroy', [$workspace, '__MESSAGE_ID__']) }}"
    data-current-user-id="{{ Auth::id() }}" data-has-more="{{ $chatHasMore ? 'true' : 'false' }}"
    data-target-message-id="{{ $chatTargetMessageId }}"
    data-target-message-missing="{{ $chatTargetMissing ? 'true' : 'false' }}">
    <div class="border-b border-gray-200 px-5 py-4 sm:px-6">
        <div class="flex items-center gap-3">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-600">
                <i class="fa-solid fa-comments" aria-hidden="true"></i>
            </span>
            <div>
                <h2 id="workspace-chat-heading" class="font-semibold text-gray-900">Workspace Chat</h2>
                <p class="text-sm text-gray-500">Percakapan umum untuk seluruh anggota workspace.</p>
            </div>
        </div>
    </div>

    <div class="relative">
        <div class="flex h-[32rem] flex-col">
            <div class="border-b border-gray-100 px-4 py-2 text-center">
                <button type="button"
                    class="{{ $chatHasMore ? '' : 'hidden' }} text-sm font-medium text-indigo-600 hover:text-indigo-800 disabled:cursor-not-allowed disabled:opacity-50"
                    data-chat-load-older>
                    <i class="fa-solid fa-arrow-up mr-1" aria-hidden="true"></i>
                    Load Older Messages
                </button>
            </div>

            @include('workspaces.partials.show.chat._message-list')

            <button type="button"
                class="absolute bottom-24 left-1/2 hidden -translate-x-1/2 rounded-full bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-lg hover:bg-indigo-700"
                data-chat-new-indicator>
                New messages
            </button>

            @include('workspaces.partials.show.chat._composer')
        </div>
    </div>
</div>
