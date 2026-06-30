@extends('layouts.app')

@section('content')
    <div class="bg-gradient-to-br from-gray-50 to-blue-50 min-h-screen">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-800 mb-1">
                Welcome {{ Auth::user()->name }}
            </h1>
            <p class="text-sm text-gray-500">Company: {{ Auth::user()->company ?? '.....' }}</p>
        </div>

        {{-- Stats Grid --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
            {{-- Open Tasks --}}
            <div
                class="bg-gradient-to-br from-cyan-50 to-blue-50 border border-cyan-100 rounded-lg p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-4xl font-bold text-cyan-600 mb-2">{{ $openTasks }}</div>
                        <div class="text-sm font-medium text-gray-600">Open Tasks</div>
                    </div>
                    <div class="text-cyan-600 opacity-20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <path d="M9 11l3 3L22 4"></path>
                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Closed Tasks --}}
            <div
                class="bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100 rounded-lg p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-4xl font-bold text-blue-600 mb-2">{{ $closedTasks }}</div>
                        <div class="text-sm font-medium text-gray-600">Closed Tasks</div>
                    </div>
                    <div class="text-blue-600 opacity-20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <path d="M9 9l6 6m0-6l-6 6"></path>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Open Phases --}}
            <div
                class="bg-gradient-to-br from-sky-50 to-cyan-50 border border-sky-100 rounded-lg p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-4xl font-bold text-sky-600 mb-2">{{ $openPhases }}</div>
                        <div class="text-sm font-medium text-gray-600">Open Phases</div>
                    </div>
                    <div class="text-sky-600 opacity-20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                    </div>
                </div>
            </div>

            {{-- Closed Phases --}}
            <div
                class="bg-gradient-to-br from-slate-50 to-gray-50 border border-slate-100 rounded-lg p-6 shadow-sm hover:shadow-md transition-all duration-300 transform hover:-translate-y-1 cursor-pointer">
                <div class="flex items-center justify-between">
                    <div>
                        <div class="text-4xl font-bold text-slate-600 mb-2">{{ $closedPhases }}</div>
                        <div class="text-sm font-medium text-gray-600">Closed Phases</div>
                    </div>
                    <div class="text-slate-600 opacity-20">
                        <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24"
                            fill="none" stroke="currentColor" stroke-width="1.5">
                            <polyline points="21 8 21 21 3 21 3 8"></polyline>
                            <rect x="1" y="3" width="22" height="5"></rect>
                            <line x1="10" y1="12" x2="14" y2="12"></line>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Content Grid --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- My Tasks --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-6 bg-cyan-500 rounded-full"></div>
                        <h2 class="text-lg font-semibold text-gray-800">My Tasks</h2>
                    </div>
                    <div class="flex gap-2">
                        <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                        <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    @if (count($tasks) > 0)
                        <div class="space-y-3">
                            @foreach ($tasks as $task)
                                <div
                                    class="flex items-center justify-between p-4 rounded-lg border border-gray-100 hover:border-cyan-200 hover:bg-cyan-50/30 transition-all duration-200 group cursor-pointer">
                                    <div class="flex items-center gap-3 flex-1">
                                        <div
                                            class="w-5 h-5 rounded border-2 border-gray-300 group-hover:border-cyan-500 transition-colors">
                                        </div>
                                        <div>
                                            <div
                                                class="font-medium text-gray-700 group-hover:text-cyan-700 transition-colors">
                                                {{ $task->title }}
                                            </div>
                                            @if ($task->project)
                                                <div class="text-xs text-gray-400 mt-1">{{ $task->project }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    @if ($task->due_date)
                                        <div class="flex items-center gap-2">
                                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14"
                                                viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                                stroke-width="2"
                                                class="{{ \Carbon\Carbon::parse($task->due_date)->isPast() ? 'text-red-400' : 'text-gray-400' }}">
                                                <rect x="3" y="4" width="18" height="18" rx="2"
                                                    ry="2"></rect>
                                                <line x1="16" y1="2" x2="16" y2="6">
                                                </line>
                                                <line x1="8" y1="2" x2="8" y2="6">
                                                </line>
                                                <line x1="3" y1="10" x2="21" y2="10">
                                                </line>
                                            </svg>
                                            <span
                                                class="text-sm font-medium {{ \Carbon\Carbon::parse($task->due_date)->isPast() ? 'text-red-500' : 'text-green-600' }}">
                                                {{ \Carbon\Carbon::parse($task->due_date)->format('m-d-Y') }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1" class="mb-4 opacity-30">
                                <path d="M9 11l3 3L22 4"></path>
                                <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path>
                            </svg>
                            <p class="text-sm">No tasks assigned to you yet.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- My Phases --}}
            <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <div class="w-1 h-6 bg-sky-500 rounded-full"></div>
                        <h2 class="text-lg font-semibold text-gray-800">My Phases</h2>
                    </div>
                    <div class="flex gap-2">
                        <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </button>
                        <button class="p-2 hover:bg-gray-100 rounded-lg transition-colors">
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="2" class="text-gray-400">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="p-6">
                    @if (count($phases) > 0)
                        <div class="space-y-3">
                            @foreach ($phases as $phase)
                                <div
                                    class="flex items-center justify-between p-4 rounded-lg border border-gray-100 hover:border-sky-200 hover:bg-sky-50/30 transition-all duration-200 group cursor-pointer">
                                    <div class="flex items-center gap-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                                            viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                            class="text-gray-400 group-hover:text-sky-500 transition-colors">
                                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                            <polyline points="2 17 12 22 22 17"></polyline>
                                            <polyline points="2 12 12 17 22 12"></polyline>
                                        </svg>
                                        <div>
                                            <div
                                                class="font-medium text-gray-700 group-hover:text-sky-700 transition-colors">
                                                {{ $phase->name }}
                                            </div>
                                            @if ($phase->project)
                                                <div class="text-xs text-gray-400 mt-1">{{ $phase->project }}</div>
                                            @endif
                                        </div>
                                    </div>
                                    @if (isset($phase->progress))
                                        <div class="flex items-center gap-2">
                                            <div class="w-24 h-2 bg-gray-100 rounded-full overflow-hidden">
                                                <div class="h-full bg-sky-500 rounded-full transition-all duration-300"
                                                    style="width: {{ $phase->progress }}%"></div>
                                            </div>
                                            <span class="text-xs font-medium text-gray-500">{{ $phase->progress }}%</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="flex flex-col items-center justify-center py-12 text-gray-400">
                            <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24"
                                fill="none" stroke="currentColor" stroke-width="1" class="mb-4 opacity-30">
                                <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                                <polyline points="2 17 12 22 22 17"></polyline>
                                <polyline points="2 12 12 17 22 12"></polyline>
                            </svg>
                            <p class="text-sm">No phases assigned to you yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
