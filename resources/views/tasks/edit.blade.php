
@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>
    <div class="max-w-8xl mx-auto px-4 sm:px-6 lg:px-8 py-4">

        {{-- Breadcrumb --}}
        <nav class="flex items-center gap-2 text-sm text-gray-500 mb-2">
            <a href="{{ route('dashboard') }}" class="hover:text-indigo-600 transition-colors">
                Home
            </a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <a href="{{ route('tasks.index') }}" class="hover:text-indigo-600 transition-colors">
                Tasks
            </a>
            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>
            <span class="text-gray-700 font-medium">Edit</span>
        </nav>

        {{-- Header --}}
        <div class="mb-4">
            <h1 class="text-3xl font-bold bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-transparent">
                Edit Task
            </h1>
            <p class="text-gray-600 mt-2">
                Update task details and rearrange the work.
            </p>
        </div>

        {{-- Card --}}
        <div class="bg-white/90 backdrop-blur-sm rounded-2xl shadow-xl border border-gray-200/60 overflow-hidden">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-[#219ebc]"></div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('tasks.update', $task->token) }}" class="space-y-6">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
                    <input type="hidden" name="back_url" value="{{ $back_url }}">
                    <input type="hidden" name="view" value="{{ request('view', 'kanban', 'gantt') }}">

                    {{-- GRID LAYOUT: 2 KOLOM --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">

                        {{-- KOLOM KIRI: Task Details --}}
                        <div class="space-y-6">

                            {{-- Project (Read-only) --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Project
                                </label>
                                <div class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-gray-700">
                                    {{ $task->project->name }}
                                    @if ($task->project->workspace)
                                        <span class="text-xs text-gray-400 ml-2">•
                                            {{ $task->project->workspace->name }}</span>
                                    @endif
                                </div>
                                <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                            </div>

                            {{-- Task Name --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Task Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="name" value="{{ old('name', $task->name) }}"
                                    placeholder="e.g. Design Homepage UI"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 @error('name') border-red-400 bg-red-50/50 @enderror"
                                    required maxlength="500" autofocus>
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
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 resize-none
                                        @error('description') border-red-400 bg-red-50/50 @enderror">{{ old('description', $task->description) }}</textarea>
                                @error('description')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Assignee --}}
                            @php
                                $currentAssignees = $task->assignees->isNotEmpty()
                                    ? $task->assignees
                                    : collect([$task->assignee])->filter();
                                $selectedAssigneeIds = old('assignee_ids', $currentAssignees->pluck('id')->all());
                            @endphp
                            <div class="relative" id="assignee-wrapper">
                                <label class="block text-sm font-semibold text-gray-800 mb-2">PIC</label>

                                <button type="button" onclick="toggleDropdown()"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl bg-white text-left focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 flex items-center justify-between">
                                    <span id="assignee-label" class="text-gray-700 truncate">
                                        @if ($currentAssignees->isNotEmpty())
                                            {{ $currentAssignees->pluck('name')->join(', ') }}
                                        @else
                                            Unassigned
                                        @endif
                                    </span>
                                    <i class="fa-solid fa-chevron-down text-gray-400 text-xs ml-2"></i>
                                </button>

                                <div id="assignee-dropdown" style="display:none;"
                                    class="absolute z-50 w-full mt-1 bg-white border border-gray-200 rounded-xl shadow-lg max-h-60 overflow-y-auto">
                                    @foreach ($assignees as $assignee)
                                        <label class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
                                            <input type="checkbox" name="assignee_ids[]" value="{{ $assignee->id }}"
                                                {{ in_array($assignee->id, $selectedAssigneeIds) ? 'checked' : '' }}
                                                onchange="updateLabel()"
                                                class="w-4 h-4 text-indigo-600 rounded border-gray-300 focus:ring-indigo-500">
                                            <span class="text-sm text-gray-700">{{ $assignee->name }}</span>
                                            @if ($assignee->id === auth()->id())
                                                <span
                                                    class="ml-auto text-xs font-semibold px-2 py-0.5 rounded-full bg-indigo-100 text-indigo-600">You</span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Status <span
                                        class="text-red-500">*</span></label>
                                <select name="status"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 bg-white @error('status') border-red-400 bg-red-50/50 @enderror"
                                    required>
                                    @foreach (['to_do', 'in_progress', 'review', 'completed', 'stopped', 'cancelled'] as $status)
                                        <option value="{{ $status }}"
                                            {{ (old('status') ?? $task->status) === $status ? 'selected' : '' }}>
                                            {{ str($status)->replace('_', ' ')->title() }}
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
                                </div>
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Tipe Dependency</label>
                                    <select name="dependency_type"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500">
                                        <option value="FS"
                                            {{ old('dependency_type', $task->dependency_type ?? 'FS') === 'FS' ? 'selected' : '' }}>
                                            FS — Finish to Start</option>
                                        <option value="SS"
                                            {{ old('dependency_type', $task->dependency_type) === 'SS' ? 'selected' : '' }}>
                                            SS —
                                            Start to Start</option>
                                        <option value="FF"
                                            {{ old('dependency_type', $task->dependency_type) === 'FF' ? 'selected' : '' }}>
                                            FF —
                                            Finish to Finish</option>
                                        <option value="SF"
                                            {{ old('dependency_type', $task->dependency_type) === 'SF' ? 'selected' : '' }}>
                                            SF —
                                            Start to Finish</option>
                                    </select>
                                </div>
                            </div>

                            {{-- Priority --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">Priority <span
                                        class="text-red-500">*</span></label>
                                <select name="priority"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 bg-white @error('priority') border-red-400 bg-red-50/50 @enderror"
                                    required>
                                    @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                                        <option value="{{ $priority }}"
                                            {{ (old('priority') ?? $task->priority) === $priority ? 'selected' : '' }}>
                                            {{ ucfirst($priority) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('priority')
                                    <div class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Weight --}}
                            <div>
                                <label class="block text-sm font-semibold text-gray-800 mb-2">
                                    Weight <span class="text-red-500">*</span>
                                </label>
                                <input type="number" name="weight" step="0.01" min="0.01"
                                    value="{{ old('weight', number_format($task->weight, 2, '.', '')) }}"
                                    placeholder="Contoh: 1.50"
                                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 @error('weight') border-red-400 bg-red-50/50 @enderror"
                                    required>
                                @error('weight')
                                    <div
                                        class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                        {{ $message }}
                                    </div>
                                @enderror
                                <p class="mt-1.5 text-xs text-gray-500">
                                    Earned Value saat ini: <span
                                        class="font-medium text-gray-700">{{ number_format($task->earned_value, 2) }}</span>
                                </p>
                            </div>

                            {{-- Dates --}}
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Start Date</label>
                                    <input type="text" name="start_date" id="start_date"
                                        value="{{ old('start_date', $task->start_date ? \Carbon\Carbon::parse($task->start_date)->format('Y-m-d') : '') }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 @error('start_date') border-red-400 bg-red-50/50 @enderror">
                                    @error('start_date')
                                        <div
                                            class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-semibold text-gray-800 mb-2">Due Date</label>
                                    <input type="text" name="due_date" id="due_date"
                                        value="{{ old('due_date', $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : '') }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all duration-200 @error('due_date') border-red-400 bg-red-50/50 @enderror">
                                    @error('due_date')
                                        <div
                                            class="mt-2 text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-3 py-2">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                        {{-- END GRID KOLOM --}}

                    </div>

                    {{-- Buttons (Left Aligned) --}}
                    <div class="flex flex-col sm:flex-row gap-3 pt-6 border-t border-gray-100 mt-8">
                        <button type="submit"
                            class="inline-flex items-center justify-center px-8 py-3 text-white
                                    font-semibold rounded-xl bg-blue-600
                                    hover:bg-blue-700
                                    shadow-lg shadow-blue-500/30
                                    transition-all duration-300 transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-check mr-2"></i>
                            Update Task
                        </button>

                        <a href="{{ url()->previous() }}"
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
        document.addEventListener('DOMContentLoaded', function () {

            // ── Assignee dropdown ────────────────────────────────────────────────
            function toggleDropdown() {
                const dropdown = document.getElementById('assignee-dropdown');
                dropdown.style.display = dropdown.style.display === 'none' ? 'block' : 'none';
            }

            function updateLabel() {
                const checkboxes = document.querySelectorAll('input[name="assignee_ids[]"]:checked');
                const label = document.getElementById('assignee-label');
                if (checkboxes.length === 0) {
                    label.textContent = 'Unassigned';
                } else {
                    const names = Array.from(checkboxes).map(cb => cb.closest('label').querySelector('span').textContent.trim());
                    label.textContent = names.join(', ');
                }
            }

            window.toggleDropdown = toggleDropdown;
            window.updateLabel    = updateLabel;

            document.addEventListener('click', function (e) {
                const wrapper = document.getElementById('assignee-wrapper');
                if (wrapper && !wrapper.contains(e.target)) {
                    document.getElementById('assignee-dropdown').style.display = 'none';
                }
            });

            // ── Flatpickr ────────────────────────────────────────────────────────
            let fpDue = flatpickr('#due_date', {
                dateFormat  : 'Y-m-d',
                defaultDate : '{{ old('due_date', $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d') : '') }}' || null,
            });

            let fpStart = flatpickr('#start_date', {
                dateFormat  : 'Y-m-d',
                defaultDate : '{{ old('start_date', $task->start_date ? \Carbon\Carbon::parse($task->start_date)->format('Y-m-d') : '') }}' || null,
                onChange    : function (selectedDates, dateStr) {
                    fpDue.set('minDate', dateStr || null);
                    if (fpDue.selectedDates[0] && fpDue.selectedDates[0] < selectedDates[0]) {
                        fpDue.setDate(dateStr);
                    }
                }
            });

            // set minDate awal kalau start_date sudah ada nilainya
            if (fpStart.selectedDates[0]) {
                fpDue.set('minDate', fpStart.input.value);
            }

            // ── Predecessor & Dependency constraint ──────────────────────────────
            const predSelect       = document.getElementById('predecessor_id');
            const dependencySelect = document.querySelector('select[name="dependency_type"]');
            const currentPred      = {{ $task->predecessor_id ?? 'null' }};
            const editProjectId    = {{ $task->project_id }};

            function addOneDay(dateStr) {
                const d = new Date(dateStr);
                d.setDate(d.getDate() + 1);
                return d.toISOString().split('T')[0];
            }

            function applyDependencyConstraint() {
                const opt        = predSelect.options[predSelect.selectedIndex];
                const dependency = dependencySelect.value;

                fpStart.set('minDate', null);
                fpDue.set('minDate', null);

                if (!opt.value || !dependency) return;

                const predStart = opt.dataset.start;
                const predDue   = opt.dataset.due;

                switch (dependency) {
                    case 'FS':
                        fpStart.set('minDate', addOneDay(predDue));
                        if (fpStart.selectedDates[0]) {
                            fpDue.set('minDate', fpStart.input.value);
                        }
                        break;
                    case 'SS':
                        fpStart.set('minDate', predStart);
                        break;
                    case 'FF':
                        fpDue.set('minDate', predDue);
                        break;
                    case 'SF':
                        fpDue.set('minDate', predStart);
                        break;
                }

                const minStartDate = fpStart.config.minDate;
                if (minStartDate && fpStart.selectedDates[0] && fpStart.selectedDates[0] < minStartDate) {
                    fpStart.clear();
                    fpDue.clear();
                }

                const minDueDate = fpDue.config.minDate;
                if (minDueDate && fpDue.selectedDates[0] && fpDue.selectedDates[0] < minDueDate) {
                    fpDue.clear();
                }
            }

            predSelect.addEventListener('change', applyDependencyConstraint);
            dependencySelect.addEventListener('change', applyDependencyConstraint);

            // ── Load predecessor tasks ───────────────────────────────────────────
            async function loadTasksEdit(projectId) {
                predSelect.innerHTML = '<option value="">— Tidak ada —</option>';
                if (!projectId) return;
                try {
                    const res  = await fetch(`/projects/${projectId}/tasks-json`);
                    const data = await res.json();
                    data.forEach(task => {
                        if (task.id == {{ $task->id }}) return;
                        const opt          = new Option(task.name, task.id, false, task.id == currentPred);
                        opt.dataset.start  = task.start_date;
                        opt.dataset.due    = task.due_date;
                        predSelect.appendChild(opt);
                    });

                    // terapkan constraint kalau ada predecessor yang sudah terpilih
                    if (currentPred) applyDependencyConstraint();

                } catch (e) {
                    console.error('Gagal load tasks:', e);
                }
            }

            loadTasksEdit(editProjectId);
        });
        </script>
        @endpush
@endsection
