@extends('layouts.app')

@section('title', 'Create Task')

@section('content')
    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
                <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">
                    Home
                </a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <a href="{{ route('tasks.index') }}" class="hover:text-indigo-600 transition-colors">
                    Tasks
                </a>
                <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
                <span class="text-gray-700 font-medium">Create</span>
            </nav>

            {{-- Header --}}
            <div class="mb-8">
                <h1
                    class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600
                       bg-clip-text text-transparent">
                    Create Task
                </h1>
                <p class="text-gray-600 mt-2">
                    Add a new task and set the details of its execution.
                </p>
            </div>

            {{-- Card --}}
            <div
                class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl
                    border border-gray-200/60 overflow-hidden max-w-3xl">

                {{-- Accent Bar --}}
                <div class="h-1.5 bg-[#219ebc]"></div>

                <div class="p-6 sm:p-8">
                    <form method="POST" action="{{ route('tasks.store') }}" class="space-y-6">
                        @csrf

                        {{-- Project --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Project <span class="text-red-500">*</span>
                            </label>
                            <select name="project_id"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                   transition-all duration-200
                                   @error('project_id') border-red-400 bg-red-50/50 @enderror"
                                required>
                                <option value="">Select project</option>
                                @foreach ($projects as $item)
                                    <option value="{{ $item->id }}"
                                        {{ old('project_id', $project?->id) == $item->id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('project_id')
                                <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <input type="hidden" name="parent_task_id" value="{{ old('parent_task_id') }}">

                        {{-- Task Name --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Task Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name') }}"
                                placeholder="e.g. Design Homepage UI"
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

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-semibold text-gray-800 mb-2">
                                Description
                            </label>
                            <textarea name="description" rows="4" placeholder="Add task details..."
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                   transition-all duration-200 resize-none">{{ old('description') }}</textarea>
                        </div>

                        {{-- Assignee --}}
                        <div class="relative" id="assignee-wrapper">
                            <label class="block text-sm font-semibold text-gray-800 mb-2">Assignee</label>

                            <button type="button" onclick="toggleDropdown()"
                                class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-left
               focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
               flex items-center justify-between">
                                <span id="assignee-label" class="text-gray-500 truncate">Pilih Assignee...</span>
                                <i class="fa-solid fa-chevron-down text-gray-400 text-xs ml-2"></i>
                            </button>

                            <div id="assignee-dropdown" style="display:none;"
                                class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                @foreach ($assignees as $assignee)
                                    <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
                                        <input type="checkbox" name="assignee_ids[]" value="{{ $assignee->id }}"
                                            {{ in_array($assignee->id, old('assignee_ids', [])) ? 'checked' : '' }}
                                            onchange="updateLabel()"
                                            class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                        <span class="text-sm text-gray-700">{{ $assignee->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        {{-- Dropdown --}}
                        <div id="assignee-dropdown"
                            class="absolute z-50 mt-2 w-full bg-white border border-gray-200 rounded-xl shadow-lg overflow-hidden hidden">
                            @foreach ($assignees as $assignee)
                                @php
                                    $hex = '#' . substr(md5($assignee->name), 0, 6);
                                    $initials = strtoupper(substr($assignee->name, 0, 1));
                                @endphp
                                <div class="assignee-option flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-indigo-50 transition-colors"
                                    data-id="{{ $assignee->id }}" data-name="{{ $assignee->name }}"
                                    data-initials="{{ $initials }}" data-color="{{ $hex }}">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium flex-shrink-0"
                                            style="background-color: {{ $hex }}22; color: {{ $hex }};">
                                            {{ $initials }}
                                        </div>
                                        <span class="text-sm text-gray-700">{{ $assignee->name }}</span>
                                    </div>
                                    {{-- Checkbox --}}
                                    <div
                                        class="assignee-checkbox w-5 h-5 rounded border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-colors">
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        {{-- Hidden inputs container --}}
                        <div id="assignee-hidden-inputs"></div>

                        @error('assignee_ids')
                            <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                {{ $message }}
                            </div>
                        @enderror
                </div>

                @push('scripts')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const trigger = document.getElementById('assignee-trigger');
                            const dropdown = document.getElementById('assignee-dropdown');
                            const chevron = document.getElementById('assignee-chevron');
                            const tagsBox = document.getElementById('assignee-selected-tags');
                            const placeholder = document.getElementById('assignee-placeholder');
                            const hiddenBox = document.getElementById('assignee-hidden-inputs');
                            const options = document.querySelectorAll('.assignee-option');

                            const selected = {}; // { id: { id, name, initials, color } }

                            // Restore old input on validation error
                            const oldIds = @json(old('assignee_ids', []));
                            oldIds.forEach(id => {
                                const el = document.querySelector(`.assignee-option[data-id="${id}"]`);
                                if (el) {
                                    selected[id] = {
                                        id: id,
                                        name: el.dataset.name,
                                        initials: el.dataset.initials,
                                        color: el.dataset.color,
                                    };
                                }
                            });

                            // Toggle dropdown
                            trigger.addEventListener('click', function() {
                                const isOpen = !dropdown.classList.contains('hidden');
                                dropdown.classList.toggle('hidden');
                                chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                            });

                            // Close on outside click
                            document.addEventListener('click', function(e) {
                                if (!document.getElementById('assignee-wrapper').contains(e.target)) {
                                    dropdown.classList.add('hidden');
                                    chevron.style.transform = 'rotate(0deg)';
                                }
                            });

                            // Option click
                            options.forEach(option => {
                                option.addEventListener('click', function() {
                                    const id = this.dataset.id;
                                    if (selected[id]) {
                                        delete selected[id];
                                    } else {
                                        selected[id] = {
                                            id: id,
                                            name: this.dataset.name,
                                            initials: this.dataset.initials,
                                            color: this.dataset.color,
                                        };
                                    }
                                    render();
                                });
                            });

                            function render() {
                                // Update checkboxes
                                options.forEach(option => {
                                    const id = option.dataset.id;
                                    const checkbox = option.querySelector('.assignee-checkbox');
                                    if (selected[id]) {
                                        option.classList.add('bg-indigo-50');
                                        checkbox.classList.add('bg-indigo-600', 'border-indigo-600');
                                        checkbox.classList.remove('border-gray-300');
                                        checkbox.innerHTML =
                                            '<i class="fa-solid fa-check text-white" style="font-size:10px"></i>';
                                    } else {
                                        option.classList.remove('bg-indigo-50');
                                        checkbox.classList.remove('bg-indigo-600', 'border-indigo-600');
                                        checkbox.classList.add('border-gray-300');
                                        checkbox.innerHTML = '';
                                    }
                                });

                                // Update tags
                                tagsBox.innerHTML = '';
                                hiddenBox.innerHTML = '';
                                const ids = Object.keys(selected);

                                if (ids.length === 0) {
                                    tagsBox.appendChild(placeholder);
                                    placeholder.style.display = 'inline';
                                } else {
                                    placeholder.style.display = 'none';
                                    ids.forEach(id => {
                                        const p = selected[id];

                                        // Tag
                                        const tag = document.createElement('span');
                                        tag.className =
                                            'inline-flex items-center gap-1.5 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-sm rounded-lg';
                                        tag.innerHTML = `
                    ${p.name}
                    <button type="button" data-remove="${id}"
                        class="text-indigo-400 hover:text-indigo-700 font-bold leading-none">×</button>
                `;
                                        tag.querySelector('button').addEventListener('click', function(e) {
                                            e.stopPropagation();
                                            delete selected[this.dataset.remove];
                                            render();
                                        });
                                        tagsBox.appendChild(tag);

                                        // Hidden input
                                        const input = document.createElement('input');
                                        input.type = 'hidden';
                                        input.name = 'assignee_ids[]';
                                        input.value = id;
                                        hiddenBox.appendChild(input);
                                    });
                                }
                            }

                            render(); // initial render (untuk restore old value)
                        });

                        function toggleDropdown() {
                            const dropdown = document.getElementById('assignee-dropdown');
                            dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
                        }

                        function updateLabel() {
                            const checkboxes = document.querySelectorAll('input[name="assignee_ids[]"]:checked');
                            const label = document.getElementById('assignee-label');
                            if (checkboxes.length === 0) {
                                label.textContent = 'Pilih Assignee...';
                            } else {
                                const names = Array.from(checkboxes).map(cb => cb.closest('label').querySelector('span').textContent
                            .trim());
                                label.textContent = names.join(', ');
                            }
                        }

                        document.addEventListener('click', function(e) {
                            const wrapper = document.getElementById('assignee-wrapper');
                            if (!wrapper.contains(e.target)) {
                                document.getElementById('assignee-dropdown').style.display = 'none';
                            }
                        });
                    </script>
                @endpush

                {{-- Status & Priority --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Status</label>
                        <select name="status"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                            @foreach (['to_do', 'in_progress', 'review', 'completed', 'stopped', 'cancelled'] as $status)
                                <option value="{{ $status }}"
                                    {{ old('status', 'to_do') === $status ? 'selected' : '' }}>
                                    {{ str($status)->replace('_', ' ')->title() }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">Priority</label>
                        <select name="priority"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                       focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                            @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                                <option value="{{ $priority }}"
                                    {{ old('priority', 'medium') === $priority ? 'selected' : '' }}>
                                    {{ ucfirst($priority) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- Weight --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                        Weight <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="weight" step="0.01" min="0.01" value="{{ old('weight', '1.00') }}"
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                   focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                        required>
                </div>

                {{-- Dates --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-800 mb-2">
                            Start Date <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="start_date" value="{{ old('start_date') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                    focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                    transition-all duration-200
                                    @error('start_date') border-red-400 bg-red-50/50 @enderror"
                            required>
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
                        <input type="date" name="due_date" value="{{ old('due_date') }}"
                            class="w-full px-4 py-3 border border-gray-300 rounded-xl
                                    focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500
                                    transition-all duration-200
                                    @error('due_date') border-red-400 bg-red-50/50 @enderror"
                            required>
                        @error('due_date')
                            <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                {{ $message }}
                            </div>
                        @enderror
                    </div>
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
                        Create Task
                    </button>

                    <a href="{{ route('tasks.index') }}"
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
