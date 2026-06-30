@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50 py-12 px-6">
    <div class="max-w-5xl mx-auto space-y-12">

        <!-- HEADER -->
        <div class="space-y-3">
            <h1 class="text-4xl font-semibold text-slate-900">
                Account Settings
            </h1>
            <p class="text-slate-500 text-lg">
                Manage your account and switch between available users on this device.
            </p>
        </div>

        <!-- SWITCH ACCOUNT -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-8">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">
                    Switch Account
                </h2>
                <p class="text-slate-500 mt-1">
                    Only accounts that have logged in on this device will appear here.
                </p>
            </div>

            <div class="space-y-6">

                @forelse ($users as $user)
                    <div
                        class="flex items-center justify-between p-6 rounded-xl border border-slate-200 hover:border-indigo-400 transition">

                        <!-- USER INFO -->
                        <div>
                            <p class="font-medium text-slate-900 text-lg">
                                {{ $user->name }}
                            </p>
                            <p class="text-slate-500">
                                {{ $user->email }}
                            </p>
                        </div>

                        <!-- ACTIONS -->
                        <div class="flex items-center gap-3">

                            @if (Auth::id() === $user->id)
                                <!-- ACTIVE -->
                                <span
                                    class="px-4 py-1.5 text-sm font-medium bg-indigo-100 text-indigo-600 rounded-full">
                                    Active
                                </span>
                            @else
                                <!-- SWITCH -->
                                <form method="POST"
                                      action="{{ route('switch.account', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="px-4 py-2 bg-indigo-600 text-white rounded-lg text-sm font-medium hover:bg-indigo-700 transition">
                                        Switch
                                    </button>
                                </form>

                                <!-- REMOVE FROM DEVICE -->
                                <form method="POST"
                                      action="{{ route('account.remove.device', $user->id) }}"
                                      onsubmit="return confirm('Hapus akun ini dari device?')">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit"
                                        class="px-4 py-2 bg-red-100 text-red-600 rounded-lg text-sm font-medium hover:bg-red-200 transition">
                                        Delete
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>
                @empty
                    <div class="text-center text-slate-500 py-8">
                        There is no other account on this device.
                    </div>
                @endforelse

            </div>
        </div>

        <!-- ACTIVE SESSIONS (OPTIONAL / STATIC) -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 space-y-6">
            <div>
                <h2 class="text-xl font-semibold text-slate-800">
                    Active Session
                </h2>
                <p class="text-slate-500 mt-1">
                    Current device session.
                </p>
            </div>

            <div class="flex items-center justify-between p-6 rounded-xl border border-slate-200">
                <div>
                    <p class="font-medium text-slate-900 text-lg">
                        {{ request()->userAgent() }}
                    </p>
                   <p class="text-slate-500">
    Active now: <span id="realtime-clock"></span>
                </p>

                    <script>
                    function updateClock() {
                        const now = new Date();

                        const options = {
                            day: '2-digit',
                            month: 'short',
                            year: 'numeric',
                            hour: '2-digit',
                            minute: '2-digit',
                            second: '2-digit'
                        };

                        document.getElementById('realtime-clock')
                            .textContent = now.toLocaleString('en-GB', options);
                    }

                    updateClock();               // jalan pertama kali
                    setInterval(updateClock, 1000); // update tiap 1 detik
                    </script>


                    {{-- <p class="text-slate-500 text-sm">
                        Last activity:
                        {{ $user->last_activity
                            ? $user->last_activity->diffForHumans()
                            : 'This account is still active'
                        }}
                    </p> --}}


                    </p>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
