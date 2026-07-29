@extends('layouts.app')

@section('title', $thread->title.' — Project Discussions')

@section('content')
    @php
        $messageIndexUrl = route('discussion.messages.index', [$project, $thread], false);
        $messageReadUrl = route('discussion.messages.read', [$project, $thread], false);
        $mentionCandidatesUrl = route('discussion.mention-candidates', [$project, $thread], false);
        $messageStoreUrl = route('discussion.messages.store', [$project, $thread], false);
        $messageUpdateUrl = route('messages.update', [$project, $thread, '__MESSAGE__'], false);
        $messageDeleteUrl = route('messages.destroy', [$project, $thread, '__MESSAGE__'], false);
    @endphp

    <div
        id="project-discussion-chat"
        class="flex h-full flex-col overflow-hidden bg-gray-100"
        data-message-index-url="{{ $messageIndexUrl }}"
        data-message-read-url="{{ $messageReadUrl }}"
        data-mention-candidates-url="{{ $mentionCandidatesUrl }}"
        data-message-store-url="{{ $messageStoreUrl }}"
        data-message-update-url="{{ $messageUpdateUrl }}"
        data-message-delete-url="{{ $messageDeleteUrl }}"
        data-current-user-id="{{ auth()->id() }}"
        data-oldest-message-id="{{ $oldestMessageId }}"
        data-latest-message-id="{{ $latestMessageId }}"
        data-has-more-older="{{ $hasMoreOlder ? 'true' : 'false' }}"
        data-target-message-id="{{ $targetMessageId }}"
        data-target-message-missing="{{ $targetMessageMissing ? 'true' : 'false' }}"
    >
        @include('discussion.partials.chat._header')
        @include('discussion.partials.chat._message-list')
        @include('discussion.partials.chat._composer')
    </div>

    @include('discussion.partials.chat._edit-message-modal')
    @include('discussion.partials.chat._delete-message-modal')
@endsection
