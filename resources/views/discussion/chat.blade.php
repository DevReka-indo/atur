@extends('layouts.app')

@section('title', $thread->title.' — Project Discussions')

@section('content')
    @php
        $messageStoreUrl = route('discussion.messages.store', [$project, $thread], false);
        $messageUpdateUrl = route('messages.update', [$project, $thread, '__MESSAGE__'], false);
        $messageDeleteUrl = route('messages.destroy', [$project, $thread, '__MESSAGE__'], false);
    @endphp

    <div
        id="project-discussion-chat"
        class="flex h-full flex-col overflow-hidden bg-gray-100"
        data-message-store-url="{{ $messageStoreUrl }}"
        data-message-update-url="{{ $messageUpdateUrl }}"
        data-message-delete-url="{{ $messageDeleteUrl }}"
        data-current-user-id="{{ auth()->id() }}"
        data-current-user-name="{{ auth()->user()->name }}"
    >
        @include('discussion.partials.chat._header')
        @include('discussion.partials.chat._message-list')
        @include('discussion.partials.chat._composer')
    </div>

    @include('discussion.partials.chat._edit-message-modal')
@endsection
