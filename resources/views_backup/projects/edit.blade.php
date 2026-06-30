@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 ">

        {{--  HEADER  --}}
        <div class="mb-8">

            {{-- Top Section: Breadcrumb --}}
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <nav class="flex items-center gap-2 text-sm text-gray-500">
                        <a href="{{ route('projects.index') }}"
                            class="hover:text-indigo-600 transition-colors flex items-center gap-1">
                            <i class="fa-solid fa-list"></i>
                            <span>Project list</span>
                        </a>

                        <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

                        <span class="text-gray-700 font-medium">
                            Edit project
                        </span>
                    </nav>
                </div>
            </div>

            {{-- Main Header --}}
            <div class="flex items-center gap-3">
                <div class="p-2 bg-indigo-100 rounded-lg">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                    </svg>
                </div>

                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        Edit Project
                    </h1>
                    <p class="text-sm text-gray-500">
                        Update project details and settings
                    </p>
                </div>
            </div>

        </div>

        {{-- form card --}}
        <div class="bg-white/95 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/60 overflow-hidden">
            {{-- Top Accent Bar --}}
            <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-violet-500 to-purple-500"></div>
            <form method="POST" action="{{ route('projects.update', $project) }}" class="p-6 sm:p-4 space-y-4">
                @csrf
                @method('PUT')

                <div class="mb-4"><label for="workspace_id"
                        class="block text-sm font-medium text-gray-700 mb-2">Workspace</label><select name="workspace_id"
                        id="workspace_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach ($workspaces as $workspace)
                            <option value="{{ $workspace->id }}"
                                {{ old('workspace_id', $project->workspace_id) == $workspace->id ? 'selected' : '' }}>
                                {{ $workspace->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4"><label for="name"
                        class="block text-sm font-medium text-gray-700 mb-2">Name</label><input type="text"
                        name="name" id="name" value="{{ old('name', $project->name) }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg" required></div>


                <div class="mb-6"><label for="status"
                        class="block text-sm font-medium text-gray-700 mb-2">Status</label><select name="status"
                        id="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                        @foreach (['planning', 'active', 'on_hold', 'completed', 'cancelled'] as $status)
                            <option value="{{ $status }}"
                                {{ old('status', $project->status) === $status ? 'selected' : '' }}>
                                {{ str($status)->replace('_', ' ')->title() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div><label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="date" name="start_date" id="start_date"
                            value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                    <div><label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input
                            type="date" name="end_date" id="end_date"
                            value="{{ old('end_date', optional($project->end_date)->format('Y-m-d')) }}"
                            class="w-full px-3 py-2 border border-gray-300 rounded-lg"></div>
                </div>

                <div class="mb-4"><label for="description"
                        class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="description" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-lg">{{ old('description', $project->description) }}</textarea>
                </div>

                <div class="flex gap-3"><button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg">Update
                        Project</button><a href="{{ route('projects.index') }}"
                        class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium py-2 px-4 rounded-lg">Cancel</a>
                </div>


            </form>
        </div>
    </div>
@endsection
