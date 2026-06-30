@extends('layouts.app')

@section('title', 'Create Project')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">
                    Home
                </a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <a href="{{ route('projects.index') }}" class="hover:text-indigo-600 transition-colors">
                    Projects
                </a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <span class="text-gray-700 font-medium">Create</span>
            </nav>

            {{-- Header --}}
            <div class="mb-8">
                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600
                       bg-clip-text text-transparent">
                    Create Project
                </h1>
                <p class="text-gray-600 mt-2">
                    Create a new project and set timeline and status.
                </p>
            </div>

            {{-- Card --}}
            <div
                class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl
                    border border-gray-200/60 overflow-hidden max-w-3xl">

                {{-- Accent Bar --}}
                <div class="h-1.5 bg-[#CCD5AE]"></div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('projects.store') }}" class="space-y-6">
                        @csrf

                        {{-- Workspace --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Workspace <span class="text-red-500">*</span>
                            </label>
                            <select name="workspace_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                   transition-all duration-200
                                   @error('workspace_id') border-red-400 bg-red-50/50 @enderror"
                                required>
                                <option value="">Select workspace</option>
                                @foreach ($workspaces as $workspace)
                                    <option value="{{ $workspace->id }}"
                                        {{ old('workspace_id', request('workspace_id')) == $workspace->id ? 'selected' : '' }}>
                                        {{ $workspace->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('workspace_id')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- Project Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Project Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                placeholder="e.g. Website Redesign"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                   transition-all duration-200
                                   @error('name') border-red-400 bg-red-50/50 @enderror"
                                required>
                            @error('name')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        {{-- start date dan due date --}}
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                                        Start Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date"
                                        name="start_date"
                                        value="{{ old('start_date') }}"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                                focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                                transition-all duration-200
                                                @error('start_date') border-red-400 bg-red-50/50 @enderror">
                                    @error('start_date')
                                        <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                                        Due Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="date"
                                        name="due_date"
                                        value="{{ old('due_date') }}"
                                        required
                                        min="{{ old('start_date') ?: date('Y-m-d') }}"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                                focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                                transition-all duration-200
                                                @error('due_date') border-red-400 bg-red-50/50 @enderror">
                                    @error('due_date')
                                        <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status"
                                        required
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                            focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                            transition-all duration-200 bg-white
                                            @error('status') border-red-400 bg-red-50/50 @enderror">
                                    <option value="" disabled selected>Select a status...</option>
                                    @foreach (['to_do' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'completed' => 'Completed', 'blocked' => 'Blocked', 'cancelled' => 'Cancelled'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('status', 'to_do') === $value ? 'selected' : '' }}>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Description
                            </label>
                            <textarea name="description" rows="4" placeholder="Add project details..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                   transition-all duration-200 resize-none">{{ old('description') }}</textarea>
                        </div>

                        {{-- Buttons --}}
                        <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t border-gray-100">
                             <button type="submit"
                                class="inline-flex items-center justify-center px-6 py-3 text-white
                                    font-semibold rounded-xl bg-blue-600
                                    hover:bg-blue-700
                                    shadow-lg shadow-blue-500/30
                                    transition-all duration-300 transform hover:-translate-y-0.5">
                                <i class="fa-solid fa-check mr-2"></i>
                                Create Project
                            </button>

                            <a href="{{ route('projects.index') }}"
                                class="inline-flex items-center justify-center px-6 py-3 text-gray-700
                                   font-medium rounded-xl border border-gray-300 bg-white
                                   hover:bg-gray-50 transition-all duration-200">
                                <i class="fa-solid fa-xmark mr-2"></i>
                                Cancel
                            </a>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
