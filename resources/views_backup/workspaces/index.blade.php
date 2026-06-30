@extends('layouts.app')

@section('title', 'Workspaces')

@section('content')
    <div class="min-h-screen bg-gray-50">
        {{-- Header --}}
        <div class="mb-2 px-4 sm:px-4 lg:py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">
                        Workspaces
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage your workspace and team collaboration in a more structured way.
                    </p>
                </div>

                <a href="{{ route('workspaces.create') }}"
                    class="group inline-flex items-center px-5 py-2.5 text-white font-medium rounded-xl
                       bg-indigo-600 hover:bg-indigo-700 shadow-lg shadow-indigo-500/30 transition-all duration-300">
                    <i class="fa-solid fa-plus mr-2 transition-transform group-hover:rotate-90"></i>
                    Create Workspace
                </a>
            </div>
        </div>

        {{-- Empty State --}}
        @if ($workspaces->isEmpty())
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-12 text-center">
                <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-indigo-50">
                    <i class="fa-solid fa-layer-group text-indigo-600 text-xl"></i>
                </div>

                <h3 class="mt-6 text-lg font-semibold text-gray-900">
                    There is no workspace yet
                </h3>

                <p class="mt-2 text-sm text-gray-500 max-w-md mx-auto">
                    Workspace helps you manage projects and team members in one place.
                    Start by creating your first workspace.
                </p>

                <div class="mt-6">
                    <a href="{{ route('workspaces.create') }}"
                        class="inline-flex items-center px-5 py-2.5 text-sm font-medium
                               text-white bg-indigo-600 hover:bg-indigo-700
                               rounded-xl transition">
                        Make Workspace
                    </a>
                </div>
            </div>
        @else
            {{-- Grid --}}
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">

                @php
                    $colors = [
                        ['bg' => 'bg-red-100', 'text' => 'text-red-600'],
                        ['bg' => 'bg-green-100', 'text' => 'text-green-600'],
                        ['bg' => 'bg-blue-100', 'text' => 'text-blue-600'],
                        ['bg' => 'bg-yellow-100', 'text' => 'text-yellow-600'],
                        ['bg' => 'bg-purple-100', 'text' => 'text-purple-600'],
                        ['bg' => 'bg-pink-100', 'text' => 'text-pink-600'],
                    ];
                @endphp
                @foreach ($workspaces as $index => $workspace)
                @php
                    $color = $colors[$index % count($colors)];
                @endphp
                    <div
                        class="group bg-white border border-gray-200 rounded-2xl
                                shadow-sm hover:shadow-lg
                                transition-all duration-300 hover:-translate-y-1">

                        <div class="p-6">

                            {{-- Title --}}
                            <div class="flex items-start justify-between">
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900 group-hover:text-[#0096c7] transition">
                                        {{ $workspace->name }}
                                    </h2>
                                    <p class="text-xs text-gray-400 mt-1">
                                        Active Workspace
                                    </p>
                                </div>

                                <div
                                    class="w-10 h-10 flex items-center justify-center
                                            rounded-lg {{ $color['bg'] }} {{ $color['text'] }} text-sm font-semibold">
                                    {{ strtoupper(substr($workspace->name, 0, 1)) }}
                                </div>
                            </div>

                            {{-- Description --}}
                            <p class="mt-4 text-sm text-gray-600 leading-relaxed">
                                {{ \Illuminate\Support\Str::limit($workspace->description, 110) ?: 'There is no description for this workspace.' }}
                            </p>

                            {{-- Divider --}}
                            <div class="my-6 border-t border-gray-100"></div>

                            {{-- Stats --}}
                            <div class="flex justify-between text-sm">
                                <div>
                                    <p class="text-gray-400">Projects</p>
                                    <p class="mt-1 font-semibold text-gray-900">
                                        {{ $workspace->projects_count }}
                                    </p>
                                </div>
                                {{-- Members --}}
                                <div class="flex items-center">
                                    @foreach ($workspace->members->take(3) as $member)
                                        <div class="-ml-2 first:ml-0">
                                            @if (!empty($member->profile_photo))
                                                <img src="{{ asset('storage/' . $member->profile_photo) }}"
                                                    class="w-8 h-8 rounded-full border-2 border-white object-cover"
                                                    alt="{{ $member->name }}">
                                            @else
                                                <div
                                                    class="w-8 h-8 rounded-full border-2 border-white
                                                            flex items-center justify-center text-xs font-semibold
                                                            bg-indigo-500 text-white">
                                                    {{ strtoupper(substr($member->name, 0, 2)) }}
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach

                                    @if ($workspace->members->count() > 3)
                                        <div class="-ml-2">
                                            <div
                                                class="w-8 h-8 rounded-full border-2 border-white
                                                        flex items-center justify-center text-xs font-semibold
                                                        bg-gray-200 text-gray-600">
                                                +{{ $workspace->members->count() - 3 }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Action --}}
                            <div class="mt-6">
                                <a href="{{ route('workspaces.show', $workspace) }}"
                                    class="inline-flex items-center text-sm font-semibold
                                           text-blue-600 hover:text-indigo-700 transition">
                                    Open Workspace
                                    <svg class="w-4 h-4 ml-1 transition-transform group-hover:translate-x-1" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7" />
                                    </svg>
                                </a>
                            </div>

                        </div>
                    </div>
                @endforeach

            </div>

        @endif

    </div>
@endsection
