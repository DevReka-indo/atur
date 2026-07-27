<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Join Workspace — {{ config('app.name') }}</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap"
        rel="stylesheet">

    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'sans-serif']
                    },
                    colors: {
                        warm: {
                            50: '#FDFDFC',
                            100: '#f4f1ea',
                            200: '#FAEDCD'
                        }
                    }
                }
            }
        }
    </script>

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

        .btn-hover {
            transition: all 0.2s ease;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
        }
    </style>
</head>

<body
    class="min-h-screen bg-gradient-to-br from-warm-50 via-warm-100 to-warm-200 flex items-center justify-center px-4 py-8">

    <div class="w-full max-w-md animate-fade-in-up">

        {{-- Main Card --}}
        <div
            class="bg-white/95 backdrop-blur-sm rounded-3xl shadow-2xl border border-gray-200/60 overflow-hidden animate-fade-in-up-delay-2">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-purple-500"></div>

            {{-- Header --}}
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-6 py-6 text-white">

                <div class="flex items-center gap-4">
                    <div
                        class="w-14 h-14 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/30
                    flex items-center justify-center text-2xl font-bold shadow-lg flex-shrink-0">
                        {{ strtoupper(substr($workspace->name, 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-indigo-100 uppercase tracking-wide">Kamu diundang ke</p>
                        <p class="font-semibold text-lg truncate">{{ $workspace->name }}</p>
                        <div class="flex items-center gap-1 mt-1 text-xs text-indigo-100">
                            <span>Oleh</span>
                            <span class="text-white font-medium">{{ $workspace->creator->name }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Body --}}
            <div class="px-6 py-6">

                <h2
                    class="text-xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent mb-2">
                    Bergabung ke Workspace
                </h2>
                <p class="text-gray-600 text-sm leading-relaxed mb-5">
                    Kamu diundang untuk bergabung ke workspace
                    <span class="font-bold text-gray-900">"{{ $workspace->name }}"</span>
                    sebagai <span class="font-semibold text-indigo-600">{{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}</span>.
                </p>

                {{-- Workspace Detail Card --}}
                <div
                    class="mb-6 p-4 rounded-2xl bg-gradient-to-br from-gray-50/80 to-gray-100/80 border border-gray-200/60 flex items-center gap-4">
                    <div
                        class="flex-shrink-0 w-12 h-12 rounded-xl bg-gradient-to-br from-violet-100 to-purple-100 flex items-center justify-center shadow-sm">
                        <i class="fa-solid fa-folder-tree text-violet-600 text-lg"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Workspace</p>
                        <p class="text-sm font-bold text-gray-900 truncate">{{ $workspace->name }}</p>
                        @if ($workspace->description)
                            <p class="text-xs text-gray-500 mt-0.5 truncate">
                                {{ Str::limit($workspace->description, 50) }}</p>
                        @endif
                    </div>
                    <div class="flex-shrink-0">
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-1 rounded-full font-medium">
                            <i class="fa-solid fa-user mr-1"></i>
                            {{ \App\Models\Workspace::roleLabel(\App\Models\Workspace::ROLE_MEMBER) }}
                        </span>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex flex-col sm:flex-row gap-3">

                    {{-- Gabung --}}
                    <form action="{{ route('workspaces.invite.accept', $workspace->token) }}" method="POST"
                        class="flex-1">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit"
                            class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-700 hover:to-violet-700 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-500/30 hover:shadow-indigo-500/50 btn-hover flex items-center justify-center gap-2">
                            <i class="fa-solid fa-check"></i>
                            Gabung Sekarang
                        </button>
                    </form>

                    {{-- Tolak --}}
                    <form action="{{ route('workspaces.invite.decline', $workspace->token) }}" method="POST"
                        class="flex-1">
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit"
                            class="w-full py-3 px-4 bg-white hover:bg-gray-50 text-gray-700 text-sm font-semibold rounded-xl border border-gray-300 hover:border-gray-400 btn-hover flex items-center justify-center gap-2 shadow-sm hover:shadow-md transition-all">
                            <i class="fa-solid fa-xmark"></i>
                            Tolak
                        </button>
                    </form>

                </div>

                {{-- Security Note --}}
                <div class="mt-6 pt-4 border-t border-gray-100">
                    <p class="text-xs text-gray-400 flex items-center justify-center gap-1.5">
                        <i class="fa-solid fa-shield-halved"></i>
                        Link ini dapat digunakan oleh siapapun yang memilikinya.
                    </p>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 bg-gradient-to-r from-gray-50/80 to-gray-100/80 border-t border-gray-100">
                <p class="text-xs text-gray-500 text-center">
                    Questions? Contact
                    <a href="mailto:{{ $workspace->creator->email }}"
                        class="text-indigo-600 font-medium hover:text-indigo-700 hover:underline transition-colors">
                        {{ $workspace->creator->email }}
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
