@extends('layouts.app')

@section('title', 'Notifications')

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-slate-50 to-blue-50/40"></div>

    <main
        class="mx-auto w-full max-w-7xl px-4 py-6 sm:px-6 lg:px-8"
        data-notifications-page
    >
        @include('notifications.partials._header')

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <section class="min-w-0 lg:col-span-2" aria-labelledby="notification-list-heading">
                <h2 id="notification-list-heading" class="sr-only">Notification list</h2>

                @include('notifications.partials._filters')

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div class="divide-y divide-slate-100" id="notifications-list">
                        @forelse ($notifications as $item)
                            @include('notifications.partials._card', ['item' => $item])
                        @empty
                            @include('notifications.partials._empty-state')
                        @endforelse
                    </div>

                    @if ($notifications->hasPages())
                        <div class="border-t border-slate-200 bg-slate-50/70 px-4 py-4 sm:px-6">
                            {{ $notifications->links() }}
                        </div>
                    @endif
                </div>
            </section>

            @include('notifications.partials._deadline-panel')
        </div>
    </main>
@endsection
