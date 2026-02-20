<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'PM Tool')</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" referrerpolicy="no-referrer" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 text-slate-800" x-data="{ mobileSidebarOpen: false, userMenuOpen: false }">
    <div class="min-h-screen flex">
        <aside class="hidden lg:flex lg:w-72 lg:flex-col lg:justify-between bg-slate-900 text-slate-200 shadow-2xl">
            <div>
                <div class="px-6 py-6 border-b border-slate-800">
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-3 text-white">
                        <span class="w-10 h-10 inline-flex items-center justify-center rounded-xl bg-indigo-500/20 text-indigo-300">
                            <i class="fa-solid fa-chart-line"></i>
                        </span>
                        <span>
                            <span class="block text-lg font-bold leading-tight">PM Tool</span>
                            <span class="block text-xs text-slate-400">Project Workspace</span>
                        </span>
                    </a>
                </div>
            </div>

                <nav class="px-4 py-6 space-y-2">
                    <a href="{{ route('dashboard') }}" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('dashboard') ? 'bg-indigo-500/20 text-indigo-200 border border-indigo-400/40' : 'hover:bg-slate-800/90 text-slate-300' }}">
                        <i class="fa-solid fa-gauge w-5 text-center"></i>
                        <span class="font-medium">Dashboard</span>
                    </a>
                    <a href="{{ route('workspaces.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('workspaces.*') ? 'bg-indigo-500/20 text-indigo-200 border border-indigo-400/40' : 'hover:bg-slate-800/90 text-slate-300' }}">
                        <i class="fa-solid fa-layer-group w-5 text-center"></i>
                        <span class="font-medium">Workspaces</span>
                    </a>
                    <a href="{{ route('projects.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('projects.*') ? 'bg-indigo-500/20 text-indigo-200 border border-indigo-400/40' : 'hover:bg-slate-800/90 text-slate-300' }}">
                        <i class="fa-solid fa-diagram-project w-5 text-center"></i>
                        <span class="font-medium">Projects</span>
                    </a>
                    <a href="{{ route('tasks.index') }}" class="group flex items-center gap-3 px-4 py-3 rounded-xl transition {{ request()->routeIs('tasks.*') ? 'bg-indigo-500/20 text-indigo-200 border border-indigo-400/40' : 'hover:bg-slate-800/90 text-slate-300' }}">
                        <i class="fa-solid fa-list-check w-5 text-center"></i>
                        <span class="font-medium">My Tasks</span>
                    </a>
                </nav>

                <div class="px-4">
                    <div class="rounded-2xl bg-gradient-to-br from-indigo-500/20 to-cyan-400/10 border border-indigo-400/20 p-4">
                        <p class="text-xs uppercase tracking-wide text-indigo-200/80">Quick Tip</p>
                        <p class="mt-2 text-sm text-slate-200">Pantau progress harian dan update task agar timeline project tetap sehat.</p>
                    </div>
                </div>
            </div>

            <div class="p-4 border-t border-slate-800">
                <div class="rounded-xl bg-slate-800/80 p-4">
                    <p class="text-sm font-semibold text-white">{{ Auth::user()->name }}</p>
                    <p class="text-xs text-slate-400">{{ Auth::user()->email }}</p>
                    <form method="POST" action="{{ route('logout') }}" class="mt-3">
                        @csrf
                        <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-slate-700 hover:bg-slate-600 px-3 py-2 text-sm text-white transition">
                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        <div class="flex-1">
            <header class="sticky top-0 z-30 bg-white/90 backdrop-blur border-b border-slate-200">
                <div class="px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg border border-slate-200 text-slate-600">
                            <i class="fa-solid fa-bars"></i>
                        </button>
                        <div>
                            <p class="text-xs uppercase tracking-wide text-slate-400">Workspace</p>
                            <h1 class="text-sm sm:text-base font-semibold text-slate-800">@yield('title', 'PM Tool')</h1>
                        </div>
                    </div>

                    <div class="relative">
                        <button @click="userMenuOpen = !userMenuOpen" class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 hover:bg-slate-50">
                            <i class="fa-solid fa-user"></i>
                            <span>{{ Auth::user()->name }}</span>
                            <i class="fa-solid fa-chevron-down text-xs"></i>
                        </button>
                        <div x-show="userMenuOpen" @click.away="userMenuOpen = false" class="absolute right-0 mt-2 w-48 rounded-xl border border-slate-200 bg-white shadow-lg p-2" style="display:none;">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="w-full text-left rounded-lg px-3 py-2 text-sm text-slate-700 hover:bg-slate-100">
                                    <i class="fa-solid fa-right-from-bracket mr-2"></i>Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mt-4 space-y-3">
                @if (session('success'))
                    <div class="bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-circle-check mt-0.5"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg flex items-start gap-2">
                        <i class="fa-solid fa-circle-xmark mt-0.5"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @if ($errors->any())
                    <div class="bg-amber-50 border border-amber-200 text-amber-900 px-4 py-3 rounded-lg">
                        <p class="font-semibold mb-1 inline-flex items-center gap-2"><i class="fa-solid fa-triangle-exclamation"></i>Validation error</p>
                        <ul class="list-disc pl-5 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>

            <main class="py-8">
                @yield('content')
            </main>
        </div>
    </div>

    <div x-show="mobileSidebarOpen" class="fixed inset-0 z-40 lg:hidden" style="display:none;">
        <div class="absolute inset-0 bg-black/50" @click="mobileSidebarOpen = false"></div>
        <aside class="absolute inset-y-0 left-0 w-72 bg-slate-900 text-slate-200 p-4 shadow-2xl">
            <div class="flex items-center justify-between mb-6">
                <a href="{{ route('dashboard') }}" class="text-white font-bold inline-flex items-center gap-2"><i class="fa-solid fa-chart-line"></i> PM Tool</a>
                <button @click="mobileSidebarOpen = false" class="w-8 h-8 rounded-md bg-slate-800"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <nav class="space-y-2">
                <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('dashboard') ? 'bg-indigo-500/20 text-indigo-200' : 'hover:bg-slate-800' }}">Dashboard</a>
                <a href="{{ route('workspaces.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('workspaces.*') ? 'bg-indigo-500/20 text-indigo-200' : 'hover:bg-slate-800' }}">Workspaces</a>
                <a href="{{ route('projects.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('projects.*') ? 'bg-indigo-500/20 text-indigo-200' : 'hover:bg-slate-800' }}">Projects</a>
                <a href="{{ route('tasks.index') }}" class="block px-3 py-2 rounded-lg {{ request()->routeIs('tasks.*') ? 'bg-indigo-500/20 text-indigo-200' : 'hover:bg-slate-800' }}">My Tasks</a>
            </nav>
        </aside>
    </div>

    @stack('scripts')
</body>
</html>
