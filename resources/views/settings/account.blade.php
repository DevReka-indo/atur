@extends('layouts.app')

@section('title', 'Account Settings')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-slate-50 via-white to-slate-100/50 -z-10"></div>

    <div class="max-w-8xl mx-auto px-4 sm:px-6 py-8 space-y-8 lg:px-8 pt-2 pb-8">

        {{-- HEADER --}}
        <div class="pb-6 border-b border-slate-200">
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">
                Account Settings
            </h1>
            <p class="mt-2 text-slate-600">
                Manage your account and switch between available users on this device.
            </p>
        </div>

        {{-- SWITCH ACCOUNT --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-200 bg-slate-50/50">
                <h2 class="text-lg font-semibold text-slate-800">Switch Account</h2>
                <p class="text-sm text-slate-500 mt-1">
                    Only accounts that have logged in on this device will appear here.
                </p>
            </div>

            <div class="p-6 space-y-4">
                @forelse ($users as $user)
                    @php
                        $isActive = Auth::id() === $user->id;
                        $initials = strtoupper(substr($user->name, 0, 2));
                        $avatarColors = [
                            'from-indigo-400 to-purple-500',
                            'from-emerald-400 to-cyan-500',
                            'from-orange-400 to-pink-500',
                            'from-blue-400 to-indigo-500',
                            'from-rose-400 to-red-500',
                        ];
                        $colorIndex = crc32($user->email) % count($avatarColors);
                        $avatarColor = $avatarColors[$colorIndex];
                    @endphp

                    <div
                        class="flex items-center justify-between p-5 rounded-xl border border-slate-200
                    {{ $isActive ? 'bg-indigo-50/50 border-indigo-200' : 'hover:border-indigo-300 hover:shadow-md' }}
                    transition-all duration-200">

                        {{-- User Info --}}
                        <div class="flex items-center gap-4">
                            @if ($user->profile_photo)
                                <img src="{{ asset('storage/' . $user->profile_photo) }}" alt="{{ $user->name }}"
                                    class="w-12 h-12 rounded-xl object-cover shadow-sm">
                            @else
                                <div
                                    class="w-12 h-12 rounded-xl bg-gradient-to-br {{ $avatarColor }}
        flex items-center justify-center text-white font-semibold shadow-sm">
                                    {{ $initials }}
                                </div>
                            @endif
                            <div>
                                <div class="flex items-center gap-2">
                                    <p class="font-semibold text-slate-900">{{ $user->name }}</p>
                                    @if ($isActive)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                            <i class="fa-solid fa-check w-3 h-3 mr-1"></i>
                                            Active
                                        </span>
                                    @endif
                                </div>
                                <p class="text-sm text-slate-500">{{ $user->email }}</p>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-2">
                            @if ($isActive)
                                <span
                                    class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 rounded-lg">
                                    <i class="fa-solid fa-user-check text-slate-400"></i>
                                    Current Session
                                </span>
                            @else
                                <form method="POST" action="{{ route('switch.account', $user->id) }}">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium
                                        text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition-colors">
                                        <i class="fa-solid fa-right-from-bracket text-xs"></i>
                                        Switch
                                    </button>
                                </form>

                                <form method="POST" action="{{ route('account.remove.device', $user->id) }}"
                                    onsubmit="return confirm('Hapus akun ini dari device?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium
                                        text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition-colors">
                                        <i class="fa-solid fa-trash-can text-xs"></i>
                                        Delete
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="text-center py-12">
                        <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-slate-100 flex items-center justify-center">
                            <i class="fa-solid fa-users-slash text-slate-400 text-2xl"></i>
                        </div>
                        <p class="text-slate-600 font-medium">There is no other account on this device.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- ACTIVE SESSION --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-lg font-semibold text-slate-800">Active Session</h2>
                <p class="text-sm text-slate-500 mt-1">Current device session.</p>
            </div>

            <div class="p-6">
                <div class="flex items-start justify-between p-5 rounded-xl border border-slate-200 bg-slate-50/50">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 mb-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-white border border-slate-200 flex items-center justify-center flex-shrink-0">
                                @php
                                    $ua = request()->userAgent();
                                    $browserIcon = match (true) {
                                        str_contains($ua, 'Chrome') => 'fa-chrome text-blue-500',
                                        str_contains($ua, 'Firefox') => 'fa-firefox-browser text-orange-500',
                                        str_contains($ua, 'Safari') => 'fa-safari text-slate-700',
                                        str_contains($ua, 'Edge') => 'fa-edge text-green-500',
                                        default => 'fa-globe text-slate-400',
                                    };
                                @endphp
                                <i class="fa-brands {{ $browserIcon }} text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-slate-900 break-all text-sm leading-relaxed">
                                    {{ $ua }}
                                </p>
                                <p class="text-sm text-slate-500 mt-2">
                                    Active now: <span id="realtime-clock" class="font-mono font-medium"></span>
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex-shrink-0 ml-4">
                        <span
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-emerald-100 text-emerald-700 text-sm font-medium">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active
                        </span>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
        function updateClock() {
            const now = new Date();
            const options = {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            };
            document.getElementById('realtime-clock').textContent = now.toLocaleString('en-GB', options);
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
@endsection
