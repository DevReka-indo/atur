@extends('layouts.app')

@section('title', 'Create Task')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="w-4xl mx-auto px-4 sm:px-6 lg:px-8 pt-2 pb-8">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-6">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">Home</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <a href="{{ route('tasks.index') }}" class="hover:text-indigo-600 transition-colors">Tasks</a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <span class="text-gray-700 font-medium">Create</span>
        </nav>

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                Create Task
            </h1>
            <p class="text-gray-600 mt-2">Add a new task and set the details of its execution.</p>
        </div>

        {{-- Card --}}
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/60 overflow-hidden">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-[#219ebc]"></div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('tasks.store') }}" class="space-y-6">
                    @csrf
                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    {{-- GRID LAYOUT: 2 KOLOM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                        {{-- KOLOM KIRI: Task Details --}}
                        <div class="space-y-6">

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
                                    required {{ isset($project) ? 'disabled' : '' }}>

                                    <option value="">Select project</option>
                                    @foreach ($projects as $item)
                                        @php
                                            $selectedValue = old('project_id', $project?->id ?? request('project_id'));
                                        @endphp
                                        <option value="{{ $item->id }}"
                                            {{ $selectedValue == $item->id ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>

                                @if (isset($project))
                                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                                @endif
                                @error('project_id')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Task Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Task Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name') }}"
                                    placeholder="e.g. Design Homepage UI"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 @error('name') border-red-400 bg-red-50/50 @enderror"
                                    required>
                                @error('name')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Description</label>
                                <textarea name="description" rows="4" placeholder="Add task details..."
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 resize-none">{{ old('description') }}</textarea>
                            </div>

                            {{-- Assignee --}}
                            <div class="relative" id="assignee-wrapper">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">PIC</label>

                                <button type="button" id="assignee-trigger"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-left focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 flex items-center justify-between">
                                    <div id="assignee-selected-tags" class="flex flex-wrap gap-1.5 items-center">
                                        <span id="assignee-placeholder" class="text-gray-500">Select PIC</span>
                                    </div>
                                    <i id="assignee-chevron"
                                        class="fa-solid fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200"></i>
                                </button>

                                <div id="assignee-dropdown"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto hidden">
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
                                                @if ($assignee->id === auth()->id())
                                                    <span
                                                        class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                                @endif
                                            </div>
                                            <div
                                                class="assignee-checkbox w-5 h-5 rounded border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-colors">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Hidden inputs --}}
                                <div id="assignee-hidden-inputs"></div>

                                @error('assignee_ids')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Status</label>
                                <select name="status"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                                    @foreach (['to_do', 'in_progress', 'review', 'completed', 'stopped', 'cancelled'] as $s)
                                        <option value="{{ $s }}"
                                            {{ old('status', 'to_do') === $s ? 'selected' : '' }}>
                                            {{ str($s)->replace('_', ' ')->title() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        {{-- KOLOM KANAN: Task Relations & Settings --}}
                        <div class="space-y-6">

                            {{-- Predecessor + Dependency Type --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                                        Predecessor <span class="text-xs text-gray-400">(opsional)</span>
                                    </label>
                                    <select name="predecessor_id" id="predecessor_id"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                                        <option value="">— Tidak ada —</option>
                                    </select>
                                    <p class="mt-1 text-xs text-gray-400">Tasks that must be completed or started before
                                        this task.</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Tipe Dependency</label>
                                    <select name="dependency_type" id="dependency_type"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                                        <option value="FS"
                                            {{ old('dependency_type', 'FS') === 'FS' ? 'selected' : '' }}>
                                            FS — Finish to Start</option>
                                        <option value="SS" {{ old('dependency_type') === 'SS' ? 'selected' : '' }}>
                                            SS — Start to Start</option>
                                        <option value="FF" {{ old('dependency_type') === 'FF' ? 'selected' : '' }}>
                                            FF — Finish to Finish</option>
                                        <option value="SF" {{ old('dependency_type') === 'SF' ? 'selected' : '' }}>
                                            SF — Start to Finish</option>
                                    </select>
                                </div>
                            </div>
                            {{-- Priority --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Priority</label>
                                <select name="priority"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                                    @foreach (['low', 'medium', 'high', 'urgent'] as $p)
                                        <option value="{{ $p }}"
                                            {{ old('priority', 'medium') === $p ? 'selected' : '' }}>
                                            {{ ucfirst($p) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Weight --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Weight <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="weight" step="0.01" min="0.01"
                                    value="{{ old('weight', '1.00') }}"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500"
                                    required>
                            </div>

                            {{-- Dates --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                                        Start Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="start_date" name="start_date"
                                        value="{{ old('start_date') }}" placeholder="YYYY-MM-DD"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 @error('start_date') border-red-400 bg-red-50/50 @enderror"
                                        required>
                                    @error('start_date')
                                        <div
                                            class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">
                                        Due Date <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="due_date" name="due_date" value="{{ old('due_date') }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 @error('due_date') border-red-400 bg-red-50/50 @enderror"
                                        required>
                                    @error('due_date')
                                        <div
                                            class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                        </div>

                    </div>

                    {{-- Buttons (Left Aligned) --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-100 mt-8">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-8 py-3 text-white font-semibold rounded-xl bg-blue-600 hover:bg-blue-700 shadow-lg shadow-blue-500/30 transition-all duration-300 transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-check mr-2"></i>
                            Create Task
                        </button>

                        <a href="{{ route('tasks.index') }}"
                            class="inline-flex items-center justify-center px-8 py-3 text-gray-700 font-medium rounded-xl border border-gray-300 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fa-solid fa-xmark mr-2"></i>
                            Cancel
                        </a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    @push('scripts')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
        <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>

        <script>
            document.addEventListener('DOMContentLoaded', function() {

                // ── Assignee dropdown (tidak berubah) ────────────────────────────────
                const trigger = document.getElementById('assignee-trigger');
                const dropdown = document.getElementById('assignee-dropdown');
                const chevron = document.getElementById('assignee-chevron');
                const tagsBox = document.getElementById('assignee-selected-tags');
                const placeholder = document.getElementById('assignee-placeholder');
                const hiddenBox = document.getElementById('assignee-hidden-inputs');
                const options = document.querySelectorAll('.assignee-option');
                const selected = {};

                const oldIds = @json(old('assignee_ids', []));
                oldIds.forEach(id => {
                    const el = document.querySelector(`.assignee-option[data-id="${id}"]`);
                    if (el) selected[id] = {
                        id,
                        name: el.dataset.name,
                        initials: el.dataset.initials,
                        color: el.dataset.color
                    };
                });

                trigger.addEventListener('click', () => {
                    const isOpen = !dropdown.classList.contains('hidden');
                    dropdown.classList.toggle('hidden');
                    chevron.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(180deg)';
                });

                document.addEventListener('click', e => {
                    if (!document.getElementById('assignee-wrapper').contains(e.target)) {
                        dropdown.classList.add('hidden');
                        chevron.style.transform = 'rotate(0deg)';
                    }
                });

                options.forEach(option => {
                    option.addEventListener('click', function() {
                        const id = this.dataset.id;
                        if (selected[id]) delete selected[id];
                        else selected[id] = {
                            id,
                            name: this.dataset.name,
                            initials: this.dataset.initials,
                            color: this.dataset.color
                        };
                        render();
                    });
                });

                function render() {
                    document.querySelectorAll('.assignee-option').forEach(option => {
                        const id = option.dataset.id;
                        const cb = option.querySelector('.assignee-checkbox');
                        if (selected[id]) {
                            option.classList.add('bg-indigo-50');
                            cb.classList.add('bg-indigo-600', 'border-indigo-600');
                            cb.classList.remove('border-gray-300');
                            cb.innerHTML =
                                '<i class="fa-solid fa-check text-white" style="font-size:10px"></i>';
                        } else {
                            option.classList.remove('bg-indigo-50');
                            cb.classList.remove('bg-indigo-600', 'border-indigo-600');
                            cb.classList.add('border-gray-300');
                            cb.innerHTML = '';
                        }
                    });

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
                            const tag = document.createElement('span');
                            tag.className =
                                'inline-flex items-center gap-1.5 px-2 py-0.5 bg-indigo-100 text-indigo-700 text-sm rounded-lg';
                            tag.innerHTML =
                                `${p.name}<button type="button" data-remove="${id}" class="text-indigo-400 hover:text-indigo-700 font-bold leading-none">×</button>`;
                            tag.querySelector('button').addEventListener('click', function(e) {
                                e.stopPropagation();
                                delete selected[this.dataset.remove];
                                render();
                            });
                            tagsBox.appendChild(tag);

                            const input = document.createElement('input');
                            input.type = 'hidden';
                            input.name = 'assignee_ids[]';
                            input.value = id;
                            hiddenBox.appendChild(input);
                        });
                    }
                }
                render();

                // ── Project & Predecessor loader ─────────────────────────────────────
                const projectSelect = document.querySelector('select[name="project_id"]');
                const hiddenProjectInput = document.querySelector('input[type="hidden"][name="project_id"]');
                const predSelect = document.getElementById('predecessor_id');
                const dependencySelect = document.getElementById('dependency_type');

                // ── Flatpickr instances ──────────────────────────────────────────────
                let fpDue = flatpickr('#due_date', {
                    dateFormat: 'Y-m-d',
                    defaultDate: '{{ old('due_date') }}' || null,
                });

                let fpStart = flatpickr('#start_date', {
                    dateFormat: 'Y-m-d',
                    defaultDate: '{{ old('start_date') }}' || null,
                    onChange: function(selectedDates, dateStr) {
                        fpDue.set('minDate', dateStr || null);
                        if (fpDue.selectedDates[0] && fpDue.selectedDates[0] < selectedDates[0]) {
                            fpDue.setDate(dateStr);
                        }
                    }
                });

                // ── Helper ───────────────────────────────────────────────────────────
                function addOneDay(dateStr) {
                    const d = new Date(dateStr);
                    d.setDate(d.getDate() + 1);
                    return d.toISOString().split('T')[0];
                }

                function applyDependencyConstraint() {
                    const opt = predSelect.options[predSelect.selectedIndex];
                    const dependency = dependencySelect.value;

                    // reset semua batas dulu
                    fpStart.set('minDate', null);
                    fpDue.set('minDate', null);

                    if (!opt.value || !dependency) return;

                    const predStart = opt.dataset.start; // e.g. "2025-05-01"
                    const predDue = opt.dataset.due; // e.g. "2025-05-17"

                    switch (dependency) {
                        case 'FS':
                            // Task baru hanya bisa mulai SETELAH predecessor selesai
                            fpStart.set('minDate', addOneDay(predDue));
                            // reset due min ke start yg baru kalau perlu
                            if (fpStart.selectedDates[0]) {
                                fpDue.set('minDate', fpStart.input.value);
                            }
                            break;

                        case 'SS':
                            // Task baru mulai bersamaan / setelah predecessor mulai
                            fpStart.set('minDate', predStart);
                            break;

                        case 'FF':
                            // Task baru selesai bersamaan / setelah predecessor selesai
                            fpDue.set('minDate', predDue);
                            break;

                        case 'SF':
                            // Task baru selesai setelah predecessor mulai
                            fpDue.set('minDate', predStart);
                            break;
                    }

                    // kalau start date yang sudah dipilih lebih awal dari min baru → clear
                    const minStartDate = fpStart.config.minDate;
                    if (minStartDate && fpStart.selectedDates[0] && fpStart.selectedDates[0] < minStartDate) {
                        fpStart.clear();
                        fpDue.clear();
                    }

                    // kalau due date yang sudah dipilih lebih awal dari min baru → clear
                    const minDueDate = fpDue.config.minDate;
                    if (minDueDate && fpDue.selectedDates[0] && fpDue.selectedDates[0] < minDueDate) {
                        fpDue.clear();
                    }
                }

                if (predSelect && dependencySelect) {
                    predSelect.addEventListener('change', applyDependencyConstraint);
                    dependencySelect.addEventListener('change', applyDependencyConstraint);
                }

                // ── Load tasks per project ───────────────────────────────────────────
                async function loadTasks(projectId) {
                    if (!predSelect) return;
                    predSelect.innerHTML = '<option value="">— Tidak ada —</option>';
                    if (!projectId) return;

                    try {
                        const res = await fetch(`/projects/${projectId}/tasks-json`);
                        const data = await res.json();
                        data.forEach(task => {
                            const option = new Option(task.name, task.id);
                            option.dataset.start = task.start_date;
                            option.dataset.due = task.due_date;
                            predSelect.appendChild(option);
                        });

                        const oldPred = "{{ old('predecessor_id') }}";
                        if (oldPred) predSelect.value = oldPred;

                        // load assignees
                        const resA = await fetch(`/projects/${projectId}/assignees-json`);
                        const assigneeData = await resA.json();

                        const dd = document.getElementById('assignee-dropdown');
                        dd.querySelectorAll('.assignee-option').forEach(el => el.remove());
                        Object.keys(selected).forEach(k => delete selected[k]);

                        assigneeData.forEach(user => {
                            const isYou = user.id == {{ Auth::id() }};
                            const div = document.createElement('div');
                            div.className =
                                'assignee-option flex items-center justify-between px-4 py-3 cursor-pointer hover:bg-indigo-50 transition-colors';
                            div.dataset.id = user.id;
                            div.dataset.name = user.name;
                            div.dataset.initials = user.name.charAt(0).toUpperCase();
                            div.dataset.color = '#6366f1';

                            const avatarHtml = user.profile_photo ?
                                `<img src="/storage/${user.profile_photo}" alt="${user.name}" class="w-8 h-8 rounded-full object-cover flex-shrink-0">` :
                                `<div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium flex-shrink-0" style="background-color:#6366f122;color:#6366f1;">${user.name.charAt(0).toUpperCase()}</div>`;

                            div.innerHTML =
                                `
                        <div class="flex items-center gap-3">
                            ${avatarHtml}
                            <span class="text-sm text-gray-700">${user.name}</span>
                            ${isYou ? '<span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>' : ''}
                        </div>
                        <div class="assignee-checkbox w-5 h-5 rounded border-2 border-gray-300 flex items-center justify-center flex-shrink-0 transition-colors"></div>`;

                            div.addEventListener('click', function() {
                                const id = this.dataset.id;
                                if (selected[id]) delete selected[id];
                                else selected[id] = {
                                    id,
                                    name: this.dataset.name,
                                    initials: this.dataset.initials,
                                    color: this.dataset.color
                                };
                                render();
                            });
                            dd.appendChild(div);
                        });

                        render();
                        applyDependencyConstraint(); // terapkan ulang constraint setelah load

                    } catch (err) {
                        console.error('Gagal load data:', err);
                    }
                }

                projectSelect?.addEventListener('change', e => loadTasks(e.target.value));

                const initialProject = projectSelect?.value || hiddenProjectInput?.value;
                if (initialProject) loadTasks(initialProject);
            });
        </script>
    @endpush
@endsection
