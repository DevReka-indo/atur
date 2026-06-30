<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accept Invitation — {{ config('app.name') }}</title>

    {{-- Tailwind CSS via CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    {{-- Google Fonts --}}
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    {{-- Custom Config --}}
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif'],
                    },
                    colors: {
                        warm: {
                            50: '#FDFDFC',
                            100: '#f4f1ea',
                            200: '#FAEDCD',
                        }
                    }
                }
            }
        }
    </script>

    {{-- Custom Styles --}}
    <style>
        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in-up {
            animation: fade-in-up 0.4s ease-out forwards;
        }

        .animate-fade-in-up-delay {
            animation: fade-in-up 0.4s ease-out 0.1s forwards;
            opacity: 0;
        }

        .animate-fade-in-up-delay-2 {
            animation: fade-in-up 0.4s ease-out 0.2s forwards;
            opacity: 0;
        }

        /* Smooth hover effects */
        .btn-hover {
            transition: all 0.2s ease;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 6px;
        }

        ::-webkit-scrollbar-track {
            background: transparent;
        }

        ::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 3px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</head>

<body
    class="min-h-screen bg-gradient-to-br from-warm-50 via-warm-100 to-warm-200 flex items-center justify-center px-4 py-8">

    <div class="w-full max-w-md animate-fade-in-up">

        {{-- LOGO Section --}}
        <div class="flex-shrink-0 flex flex-col items-center justify-center px-4 py-6 gap-3 animate-fade-in-up-delay">
            <a href="{{ route('dashboard') }}" class="flex flex-col items-center gap-3 group">
                {{-- Logo with hover effect --}}
                <div class="relative">
                    @include('layouts.logo', [
                        'class' => 'h-20 transition-transform duration-300 group-hover:scale-105',
                    ])
                    {{-- Subtle glow effect --}}
                    <div
                        class="absolute inset-0 bg-gradient-to-r from-indigo-500/20 to-violet-500/20 blur-xl rounded-full -z-10 opacity-0 group-hover:opacity-100 transition-opacity">
                    </div>
                </div>
                <div class="text-center leading-tight">
                    <p class="text-xs font-medium text-gray-600">Project Management</p>
                    <p class="text-[10px] text-gray-400 mt-0.5">Collaborate. Create. Complete.</p>
                </div>
            </a>
        </div>

        {{-- Main Card --}}
        <div
            class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-gray-200/60 overflow-hidden animate-fade-in-up-delay-2">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-purple-500"></div>

            {{-- Header Section --}}
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white">
                <div class="flex items-center gap-4">
                    {{-- Avatar Initial --}}
                    <div class="relative">
                        @php
                            $invitedUser = \App\Models\User::where('email', $invitation->email)->first();
                        @endphp

                        @if ($invitedUser && $invitedUser->profile_photo)
                            <img src="{{ asset('storage/' . $invitedUser->profile_photo) }}"
                                alt="{{ $invitedUser->name }}"
                                class="w-14 h-14 rounded-2xl object-cover shadow-lg border border-white/30">
                        @else
                            <div
                                class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm
                    border border-white/30 flex items-center justify-center
                    text-xl font-bold uppercase shadow-lg">
                                {{ substr($invitation->email, 0, 1) }}
                            </div>
                        @endif

                        {{-- Online indicator --}}
                        <span
                            class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-400 border-2 border-indigo-600 rounded-full"></span>
                    </div>
                    {{-- Online indicator --}}
                    <span
                        class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-emerald-400 border-2 border-indigo-600 rounded-full"></span>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-indigo-100 uppercase tracking-wide">Invitation for</p>
                        <p class="font-semibold text-lg truncate">{{ $invitation->email }}</p>
                        <div class="flex items-center gap-1 mt-1 text-xs text-indigo-100">
                            <span>Invited by</span>
                            <span class="text-white font-medium truncate">{{ $invitation->inviter->name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Body Section --}}
            <div class="px-6 py-6">
                {{-- Title --}}
                <h2
                    class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent mb-2">
                    Join {{ ucfirst($invitation->type) }}
                </h2>

                {{-- Description --}}
                <p class="text-gray-600 text-sm leading-relaxed mb-4">
                    You have been invited to join the
                    <span class="font-semibold text-gray-800">{{ $invitation->type }}</span>
                    <span class="font-bold text-gray-900">"{{ $invitable?->name ?? 'Unknown' }}"</span>.
                    Sign in or register with
                    <span class="font-semibold text-indigo-600">{{ $invitation->email }}</span>
                    to accept this invitation.
                </p>

                {{-- Expiry Notice --}}
                <div
                    class="mb-5 p-3 rounded-xl bg-gradient-to-r from-amber-50/80 to-orange-50/80
                            border border-amber-200/60 flex items-start gap-3">
                    <div class="flex-shrink-0 w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center">
                        <i class="fa-regular fa-clock text-amber-600 text-sm"></i>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-amber-800 uppercase tracking-wide">Expires Soon</p>
                        <p class="text-sm text-amber-700 mt-0.5">
                            {{ $invitation->expires_at->format('d F Y, H:i') }}
                        </p>
                    </div>
                </div>

                {{-- Invitation Detail Card --}}
                <div
                    class="mb-6 p-4 rounded-2xl bg-gradient-to-br from-gray-50/80 to-gray-100/80
                            border border-gray-200/60 flex items-center gap-4">

                    {{-- Icon --}}
                    <div
                        class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br
                                {{ $invitation->type === 'workspace' ? 'from-violet-100 to-purple-100' : 'from-indigo-100 to-blue-100' }}
                                flex items-center justify-center shadow-sm">
                        <i
                            class="fa-solid {{ $invitation->type === 'workspace' ? 'fa-folder-tree' : 'fa-diagram-project' }}
                                {{ $invitation->type === 'workspace' ? 'text-violet-600' : 'text-indigo-600' }} text-lg"></i>
                    </div>

                    {{-- Info --}}
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">
                            {{ $invitation->type }}
                        </p>
                        <p class="text-sm font-bold text-gray-900 truncate">
                            {{ $invitable?->name ?? 'Unknown' }}
                        </p>
                        @if ($invitable?->description)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                {{ Str::limit($invitable->description, 40) }}
                            </p>
                        @endif
                    </div>

                    {{-- Arrow indicator --}}
                    <div class="flex-shrink-0">
                        <i class="fa-solid fa-arrow-right-long text-gray-300"></i>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    {{-- Accept Button --}}
                    <form action="{{ route('invitations.store-session') }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="token" value="{{ $invitation->token }}">
                        <input type="hidden" name="redirect" value="register">
                        <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-violet-600
                                   hover:from-indigo-700 hover:to-violet-700
                                   text-white text-sm font-semibold rounded-xl
                                   shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50
                                   btn-hover flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i>
                            Masuk & Terima
                        </button>
                    </form>

                    {{-- Decline Button --}}
                    <form action="{{ route('invitations.decline') }}" method="POST" class="flex-1">
                        @csrf
                        <input type="hidden" name="token" value="{{ $invitation->token }}">
                        <button type="submit"
                            class="w-full py-3 px-4 bg-white hover:bg-gray-50
                                   text-gray-700 text-sm font-semibold rounded-xl
                                   border border-gray-300 hover:border-gray-400
                                   btn-hover flex items-center justify-center gap-2
                                   shadow-sm hover:shadow-md transition-all">
                            <i class="fa-solid fa-xmark"></i>
                            Tolak
                        </button>
                    </form>
                </div>

                {{-- Security Note --}}
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-shield-halved"></i>
                        This invitation is secure and can only be used once.
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50/80 to-gray-100/80 border-t border-gray-100">
                <p class="text-xs text-gray-500 text-center">
                    Questions? Contact
                    <a href="mailto:{{ $invitation->inviter->email }}"
                        class="text-indigo-600 font-medium hover:text-indigo-700 hover:underline transition-colors">
                        {{ $invitation->inviter->email }}
                    </a>
                </p>
            </div>
        </div>

        {{-- Footer Credit --}}
        <p class="text-center text-[10px] text-gray-400 mt-6 animate-fade-in-up-delay-2">
            © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </p>

    </div>

</body>

</html>
