@extends('layouts.app')

@section('title', 'Edit Project')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="w-8xl mx-auto px-4 sm:px-6 lg:px-8 pt-2 pb-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-4">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">Home</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <a href="{{ route('projects.index') }}" class="hover:text-indigo-600 transition-colors">Projects</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <span class="text-gray-700 font-medium">Edit</span>
        </nav>

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                Edit Project
            </h1>
            <p class="text-gray-600 mt-1">
                Update project details, timeline, and status.
            </p>
        </div>

        {{-- Card --}}
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/60 overflow-hidden">
            {{-- Accent Bar --}}
            <div class="h-1.5 bg-[#219ebc]"></div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('projects.update', $project->token) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        {{-- Row 1: Workspace + Timeline --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Workspace (Read Only) --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Workspace
                                </label>
                                <div class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700">
                                    {{ $project->workspace->name ?? '-' }}
                                </div>
                                <input type="hidden" name="workspace_id" value="{{ $project->workspace_id }}">
                            </div>

                            {{-- Timeline --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Timeline <span class="text-red-500">*</span>
                                </label>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <input type="date" name="start_date" id="start_date"
                                            value="{{ old('start_date', optional($project->start_date)->format('Y-m-d')) }}"
                                            required onchange="updateMinEndDate(this.value)"
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
                                        <input type="date" name="end_date" id="end_date"
                                            value="{{ old('end_date', optional($project->end_date)->format('Y-m-d')) }}"
                                            required onchange="validateEndDate(this.value)"
                                            min="{{ old('start_date', $project->start_date) ? \Carbon\Carbon::parse($project->start_date)->format('Y-m-d') : date('Y-m-d') }}"
                                            class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                                    focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                                    transition-all duration-200
                                                    @error('end_date') border-red-400 bg-red-50/50 @enderror">
                                        @error('end_date')
                                            <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                                {{ $message }}
                                            </div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="flex justify-between mt-2 text-xs text-gray-500">
                                    <span>Start Date</span>
                                    <span>End Date</span>
                                </div>
                            </div>
                        </div>

                        {{-- Row 2: Project Name + Status --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Project Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Project Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $project->name) }}"
                                    placeholder="e.g. Website Redesign"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 @error('name') border-red-400 bg-red-50/50 @enderror"
                                    required>
                                @error('name')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Status <span class="text-red-500">*</span>
                                </label>
                                <select name="status" required
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                            focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                            transition-all duration-200 bg-white
                                            @error('status') border-red-400 bg-red-50/50 @enderror">
                                    <option value="" disabled>Select a status...</option>
                                    @foreach (['planning' => 'Planning', 'active' => 'Active', 'on_hold' => 'On Hold', 'completed' => 'Completed', 'cancelled' => 'Cancelled', 'urgent' => 'Urgent'] as $value => $label)
                                        <option value="{{ $value }}"
                                            {{ old('status', $project->status) === $value ? 'selected' : '' }}>
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

                        {{-- Row 3: Description (Full Width) --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Description
                            </label>
                            <textarea name="description" rows="5" placeholder="Add project details..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 resize-none
                                @error('description') border-red-400 bg-red-50/50 @enderror">{{ old('description', $project->description) }}</textarea>
                            @error('description')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-6 mt-6 border-t border-gray-100">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-6 py-3 text-white
                                font-semibold rounded-xl bg-blue-600
                                hover:bg-blue-700
                                shadow-lg shadow-blue-500/30
                                transition-all duration-300 transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-check mr-2"></i>
                            Update Project
                        </button>

                        <a href="{{ route('projects.index') }}"
                            class="inline-flex items-center justify-center px-6 py-3 text-gray-700 font-medium rounded-xl border border-gray-300 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fa-solid fa-xmark mr-2"></i>
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        function updateMinEndDate(startVal) {
            const endDateInput = document.getElementById('end_date');
            if (!startVal) return;
            endDateInput.min = startVal;
        }

        function validateEndDate(endVal) {
            const startVal = document.getElementById('start_date').value;
            const endDateInput = document.getElementById('end_date');

            if (startVal && endVal < startVal) {
                endDateInput.value = '';
                alert('End date tidak boleh sebelum start date!');
            }
        }

        window.addEventListener('DOMContentLoaded', function () {
            const startVal = document.getElementById('start_date').value;
            if (startVal) {
                updateMinEndDate(startVal);
            }
        });
    </script>
@endsection
