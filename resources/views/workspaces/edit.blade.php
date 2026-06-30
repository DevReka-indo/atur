@extends('layouts.app')

@section('title', 'Edit Workspace')

@section('content')

    {{-- Background Gradient Lembut --}}
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            {{-- Breadcrumb Modern --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
                <a href="{{ route('workspaces.index') }}" class="hover:text-indigo-600 transition-colors">Workspaces</a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <a href="{{ route('workspaces.show', $workspace->token) }}" class="hover:text-indigo-600 transition-colors">
                    View
                    Workspaces</a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <span class="text-gray-700 font-medium" aria-current="page">Edit</span>
            </nav>

            {{-- Header Section --}}
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <h1
                        class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                        Edit Workspace
                    </h1>
                </div>
                <p class="text-gray-600 ml-13">Update your workspace information below.</p>
            </div>

            {{-- Form Card --}}
            <div
                class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/60
                        overflow-hidden max-w-3xl">

                {{-- Top accent bar --}}
                <div class="h-1.5 bg-[#219ebc]"></div>

                <div class="p-6 sm:p-8">
                    {{-- Workspace Preview Badge --}}
                    <div class="flex items-center gap-3 mb-6 p-4 bg-gray-50/80 rounded-xl border border-gray-200/60">
                        <div
                            class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-100 to-violet-100
                                    flex items-center justify-center text-indigo-700 font-bold">
                            {{ strtoupper(substr($workspace->name, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Workspace Name </p>
                            <p class="font-semibold text-gray-900">{{ $workspace->name }}</p>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('workspaces.update', $workspace->token) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        {{-- Name Field --}}
                        <div>
                            <label for="name"
                                class="flex items-center gap-2 block text-sm font-semibold text-gray-800 mb-2">
                                Workspace Name <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="text" name="name" id="name"
                                    value="{{ old('name', $workspace->name) }}"
                                    placeholder="e.g. Marketing Team, Project Alpha"
                                    class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200
                                    @error('name') border-red-400 bg-red-50/50 @enderror"
                                    required autofocus>
                                {{-- <i class="fa-regular fa-signature absolute left-3 top-1/2 -translate-y-1/2
                                text-gray-400"></i> --}}
                            </div>
                            @error('name')
                                <div
                                    class="mt-2 flex items-center gap-1.5 text-sm text-red-600
                                            bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    <i class="fa-solid fa-circle-exclamation"></i>
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Description Field --}}
                        <div>
                            <label for="description"
                                class="flex items-center gap-2 block text-sm font-semibold text-gray-800 mb-2">
                                Description
                            </label>
                            <div class="relative">
                                <textarea name="description" id="description" rows="4"
                                    placeholder="Workspace Description"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500/50
                                                focus:border-indigo-500 transition-all duration-200 resize-none
                                                @error('description') border-red-400 bg-red-50/50 @enderror"
                                    maxlength="500">{{ old('description', $workspace->description) }}</textarea>
                                <div class="absolute bottom-3 right-3 text-xs text-gray-400">
                                    <span
                                        id="charCount">{{ strlen(old('description', $workspace->description) ?? '') }}</span>/500
                                </div>
                            </div>
                            @error('description')
                                <div
                                    class="mt-2 flex items-center gap-1.5 text-sm text-red-600
                                            bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100">
                            <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-3 text-white
                                    font-semibold rounded-xl bg-blue-600
                                    hover:bg-blue-700
                                    shadow-lg shadow-blue-500/30
                                    transition-all duration-300 transform hover:-translate-y-0.5">
                                <i class="fa-solid fa-check mr-2"></i>
                                Update Workspace
                            </button>
                            <a href="{{ route('workspaces.index') }}"
                                class="inline-flex items-center justify-center px-6 py-3 text-gray-700
                                    font-medium rounded-xl border border-gray-300 bg-white/80
                                    hover:bg-gray-50 hover:border-gray-400
                                    transition-all duration-200 focus:outline-none focus:ring-2
                                    focus:ring-gray-400 focus:ring-offset-2">
                                <i class="fa-solid fa-xmark mr-2"></i>
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- Character Counter Script --}}
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const textarea = document.getElementById('description');
                const counter = document.getElementById('charCount');

                if (textarea && counter) {
                    const updateCount = () => {
                        counter.textContent = textarea.value.length;
                    };
                    textarea.addEventListener('input', updateCount);
                    updateCount();
                }
            });
        </script>
    @endpush
@endsection
