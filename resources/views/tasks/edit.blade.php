@extends('layouts.app')

@section('title', 'Edit Task')

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-gray-50 to-gray-100/50"></div>

    <div class="max-w-8xl mx-auto px-4 py-4 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="mb-2 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="transition-colors hover:text-indigo-600">
                Home
            </a>

            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

            <a href="{{ route('tasks.index') }}" class="transition-colors hover:text-indigo-600">
                Tasks
            </a>

            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

            <span class="font-medium text-gray-700">
                Edit
            </span>
        </nav>

        {{-- Header --}}
        <div class="mb-4">
            <h1
                class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-3xl font-bold text-transparent">
                Edit Task
            </h1>

            <p class="mt-2 text-gray-600">
                Update task details and rearrange the work.
            </p>
        </div>

        {{-- Card --}}
        <div
            class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white/90 shadow-xl backdrop-blur-sm">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-[#219ebc]"></div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('tasks.update', $task->token) }}" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <input type="hidden" name="redirect_to" value="{{ request('redirect_to') }}">
                    <input type="hidden" name="back_url" value="{{ $back_url }}">
                    <input type="hidden" name="view" value="{{ request('view', 'kanban', 'gantt') }}">

                    @if ($errors->any())
                        <div class="rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                            <ul class="list-inside list-disc space-y-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('tasks.partials._parent-context', [
                        'parentTask' => $task->parent,
                        'parentDepth' => max(0, $taskDepth - 1),
                        'usedSubtaskWeight' => $totalSiblingWeight,
                        'remainingSubtaskWeight' => max(0, 100 - $totalSiblingWeight),
                    ])

                    {{-- Grid Layout --}}
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

                        {{-- Kolom Kiri: Task Details --}}
                        <div class="space-y-6">

                            {{-- Project Read Only --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Project
                                </label>

                                <div
                                    class="w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 text-gray-700">
                                    {{ $task->project->name }}

                                    @if ($task->project->workspace)
                                        <span class="ml-2 text-xs text-gray-400">
                                            • {{ $task->project->workspace->name }}
                                        </span>
                                    @endif
                                </div>

                                <input type="hidden" name="project_id" value="{{ $task->project_id }}">
                            </div>

                            {{-- Task Name --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Task Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="name" value="{{ old('name', $task->name) }}"
                                    placeholder="e.g. Design Homepage UI"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('name') border-red-400 bg-red-50/50 @enderror"
                                    required maxlength="500" autofocus>

                                @error('name')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Description
                                </label>

                                <textarea name="description" rows="4" placeholder="Add task details..."
                                    class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('description') border-red-400 bg-red-50/50 @enderror">{{ old('description', $task->description) }}</textarea>

                                @error('description')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Assignee --}}
                            @php
                                $currentAssignees = $task->assignees->isNotEmpty()
                                    ? $task->assignees
                                    : collect([$task->assignee])->filter();

                                $selectedAssigneeIds = old(
                                    'assignee_ids',
                                    $currentAssignees->pluck('id')->all(),
                                );
                            @endphp

                            <div class="relative" id="assignee-wrapper">
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    PIC
                                </label>

                                <button type="button" onclick="toggleDropdown()"
                                    class="flex w-full items-center justify-between rounded-xl border
                                        border-gray-300 bg-white px-4 py-3 text-left
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50">

                                    <span id="assignee-label" class="truncate text-gray-700">
                                        @if ($currentAssignees->isNotEmpty())
                                            {{ $currentAssignees->pluck('name')->join(', ') }}
                                        @else
                                            Unassigned
                                        @endif
                                    </span>

                                    <i class="fa-solid fa-chevron-down ml-2 text-xs text-gray-400"></i>
                                </button>

                                <div id="assignee-dropdown" style="display: none;"
                                    class="absolute z-50 mt-1 max-h-60 w-full overflow-y-auto
                                        rounded-xl border border-gray-200 bg-white shadow-lg">

                                    @foreach ($assignees as $assignee)
                                        <label
                                            class="flex cursor-pointer items-center gap-3 px-4 py-2.5 hover:bg-gray-50">

                                            <input type="checkbox" name="assignee_ids[]"
                                                value="{{ $assignee->id }}"
                                                {{ in_array($assignee->id, $selectedAssigneeIds) ? 'checked' : '' }}
                                                onchange="updateLabel()"
                                                class="h-4 w-4 rounded border-gray-300 text-indigo-600
                                                    focus:ring-indigo-500">

                                            <span class="text-sm text-gray-700">
                                                {{ $assignee->name }}
                                            </span>

                                            @if ($assignee->id === auth()->id())
                                                <span
                                                    class="ml-auto rounded-full bg-indigo-100 px-2 py-0.5
                                                        text-xs font-semibold text-indigo-600">
                                                    You
                                                </span>
                                            @endif
                                        </label>
                                    @endforeach
                                </div>
                            </div>

                            {{-- Status --}}
                            @if ($taskHasSubtasks)
                                @include('tasks.partials._status-readonly', [
                                    'task' => $task,
                                ])
                            @else
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-800">
                                        Status
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <select name="status"
                                        class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3
                                            transition-all duration-200
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('status') border-red-400 bg-red-50/50 @enderror"
                                        required>

                                        @foreach (['to_do', 'in_progress', 'review', 'completed', 'stopped', 'cancelled'] as $status)
                                            <option value="{{ $status }}"
                                                class="{{ $task->parent_task_id !== null && $status !== 'to_do'
                                                    ? 'js-subtask-status-option'
                                                    : '' }}"
                                                {{ $task->parent_task_id !== null && !$subtaskStatusReady && $status !== 'to_do'
                                                    ? 'disabled'
                                                    : '' }}
                                                {{ (old('status') ?? $task->status) === $status ? 'selected' : '' }}>
                                                {{ str($status)->replace('_', ' ')->title() }}
                                            </option>
                                        @endforeach
                                    </select>

                                    @error('status')
                                        <div
                                            class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            @endif

                        </div>

                        {{-- Kolom Kanan: Task Relations & Settings --}}
                        <div class="space-y-6">

                            {{-- Predecessor + Dependency --}}
                            <div class="space-y-3">
                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                    {{-- Predecessor --}}
                                    <div>
                                        <label class="mb-2 block text-sm font-semibold text-gray-800">
                                            Predecessor

                                            <span class="text-xs font-normal text-gray-400">
                                                (opsional)
                                            </span>
                                        </label>

                                        <select name="predecessor_id" id="predecessor_id"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3
                                                focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50">

                                            <option value="">— Tidak ada —</option>
                                        </select>

                                        <p class="mt-1 text-xs leading-relaxed text-gray-400">
                                            Task yang menjadi acuan waktu sebelum task ini dimulai atau selesai.
                                        </p>
                                    </div>

                                    {{-- Dependency Type --}}
                                    <div>
                                        <div class="mb-2 flex items-center justify-between gap-2">
                                            <label class="block text-sm font-semibold text-gray-800">
                                                Tipe Dependency
                                            </label>

                                            <button type="button" id="dependency-help-toggle"
                                                aria-expanded="false"
                                                aria-controls="dependency-help-panel"
                                                class="inline-flex items-center gap-1 text-xs font-medium
                                                    text-sky-700 transition-colors hover:text-sky-900">

                                                <i class="fa-solid fa-circle-info"></i>

                                                <span>
                                                    Panduan
                                                </span>
                                            </button>
                                        </div>

                                        <select name="dependency_type" id="dependency_type"
                                            class="w-full rounded-xl border border-gray-300 px-4 py-3
                                                focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50">

                                            <option value="FS"
                                                {{ old('dependency_type', $task->dependency_type ?? 'FS') === 'FS'
                                                    ? 'selected'
                                                    : '' }}>
                                                FS — Finish to Start
                                            </option>

                                            <option value="SS"
                                                {{ old('dependency_type', $task->dependency_type) === 'SS'
                                                    ? 'selected'
                                                    : '' }}>
                                                SS — Start to Start
                                            </option>

                                            <option value="FF"
                                                {{ old('dependency_type', $task->dependency_type) === 'FF'
                                                    ? 'selected'
                                                    : '' }}>
                                                FF — Finish to Finish
                                            </option>

                                            <option value="SF"
                                                {{ old('dependency_type', $task->dependency_type) === 'SF'
                                                    ? 'selected'
                                                    : '' }}>
                                                SF — Start to Finish
                                            </option>
                                        </select>
                                    </div>
                                </div>

                                {{-- Dependency Help Panel --}}
                                <div id="dependency-help-panel"
                                    class="hidden overflow-hidden rounded-xl border border-sky-200 bg-sky-50/80">

                                    <div class="border-b border-sky-200 px-4 py-3">
                                        <div class="flex items-start gap-3">
                                            <div
                                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center
                                                    rounded-lg bg-sky-100 text-sky-700">
                                                <i class="fa-solid fa-link"></i>
                                            </div>

                                            <div>
                                                <h3 class="text-sm font-semibold text-sky-950">
                                                    Panduan Predecessor dan Dependency
                                                </h3>

                                                <p class="mt-1 text-xs leading-relaxed text-sky-800">
                                                    Predecessor adalah task yang menjadi acuan waktu bagi task
                                                    yang sedang diedit. Pilih tipe dependency untuk menentukan
                                                    hubungan tanggal antara kedua task.
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2">

                                        {{-- FS --}}
                                        <div class="rounded-lg border border-sky-100 bg-white p-3">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex h-7 min-w-8 items-center justify-center
                                                        rounded-md bg-blue-100 px-2 text-xs font-bold text-blue-700">
                                                    FS
                                                </span>

                                                <span class="text-sm font-semibold text-gray-900">
                                                    Finish to Start
                                                </span>
                                            </div>

                                            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                                                Task ini hanya dapat dimulai setelah predecessor selesai.
                                            </p>

                                            <p class="mt-2 text-xs font-medium text-gray-500">
                                                Contoh: Development dimulai setelah Analisis selesai.
                                            </p>
                                        </div>

                                        {{-- SS --}}
                                        <div class="rounded-lg border border-sky-100 bg-white p-3">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex h-7 min-w-8 items-center justify-center
                                                        rounded-md bg-emerald-100 px-2 text-xs font-bold text-emerald-700">
                                                    SS
                                                </span>

                                                <span class="text-sm font-semibold text-gray-900">
                                                    Start to Start
                                                </span>
                                            </div>

                                            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                                                Task ini dapat dimulai bersamaan atau setelah predecessor mulai.
                                            </p>

                                            <p class="mt-2 text-xs font-medium text-gray-500">
                                                Contoh: Dokumentasi mulai ketika Development mulai.
                                            </p>
                                        </div>

                                        {{-- FF --}}
                                        <div class="rounded-lg border border-sky-100 bg-white p-3">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex h-7 min-w-8 items-center justify-center
                                                        rounded-md bg-amber-100 px-2 text-xs font-bold text-amber-700">
                                                    FF
                                                </span>

                                                <span class="text-sm font-semibold text-gray-900">
                                                    Finish to Finish
                                                </span>
                                            </div>

                                            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                                                Task ini harus selesai bersamaan atau setelah predecessor selesai.
                                            </p>

                                            <p class="mt-2 text-xs font-medium text-gray-500">
                                                Contoh: Dokumentasi selesai setelah Development selesai.
                                            </p>
                                        </div>

                                        {{-- SF --}}
                                        <div class="rounded-lg border border-sky-100 bg-white p-3">
                                            <div class="flex items-center gap-2">
                                                <span
                                                    class="inline-flex h-7 min-w-8 items-center justify-center
                                                        rounded-md bg-violet-100 px-2 text-xs font-bold text-violet-700">
                                                    SF
                                                </span>

                                                <span class="text-sm font-semibold text-gray-900">
                                                    Start to Finish
                                                </span>
                                            </div>

                                            <p class="mt-2 text-xs leading-relaxed text-gray-600">
                                                Task ini harus selesai setelah predecessor mulai.
                                            </p>

                                            <p class="mt-2 text-xs font-medium text-gray-500">
                                                Contoh: Shift lama selesai setelah shift pengganti mulai.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="border-t border-sky-200 bg-sky-100/60 px-4 py-3">
                                        <p class="flex items-start gap-2 text-xs leading-relaxed text-sky-900">
                                            <i class="fa-solid fa-lightbulb mt-0.5"></i>

                                            <span>
                                                Gunakan dependency hanya jika jadwal task memang bergantung
                                                pada task lain. Untuk task mandiri, kosongkan predecessor.
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Priority --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Priority
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="priority"
                                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('priority') border-red-400 bg-red-50/50 @enderror"
                                    required>

                                    @foreach (['low', 'medium', 'high', 'urgent'] as $priority)
                                        <option value="{{ $priority }}"
                                            {{ (old('priority') ?? $task->priority) === $priority
                                                ? 'selected'
                                                : '' }}>
                                            {{ ucfirst($priority) }}
                                        </option>
                                    @endforeach
                                </select>

                                @error('priority')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            @include('tasks.partials._weight-readiness', [
                                'isSubtask' => $task->parent_task_id !== null,
                                'siblingWeightBase' => $siblingWeightWithoutTask,
                                'remainingSubtaskWeight' => $remainingSubtaskWeight,
                                'subtaskWeightValue' => number_format(
                                    (float) $task->subtask_weight_percentage,
                                    2,
                                    '.',
                                    '',
                                ),
                                'remainingAfterInput' => max(
                                    0,
                                    100 -
                                        $siblingWeightWithoutTask -
                                        (float) old(
                                            'subtask_weight_percentage',
                                            $task->subtask_weight_percentage,
                                        ),
                                ),
                                'legacyWeight' => number_format(
                                    (float) $task->weight,
                                    2,
                                    '.',
                                    '',
                                ),
                                'rootWeightValue' => old(
                                    'weight',
                                    number_format((float) $task->weight, 2, '.', ''),
                                ),
                                'statusUnlocked' => $task->status !== 'to_do',
                            ])

                            {{-- Dates --}}
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                {{-- Start Date --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-800">
                                        Start Date
                                    </label>

                                    <input type="text" name="start_date" id="start_date"
                                        value="{{ old(
                                            'start_date',
                                            $task->start_date
                                                ? \Carbon\Carbon::parse($task->start_date)->format('Y-m-d')
                                                : '',
                                        ) }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3
                                            transition-all duration-200
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('start_date') border-red-400 bg-red-50/50 @enderror">

                                    @error('start_date')
                                        <div
                                            class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>

                                {{-- Due Date --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-800">
                                        Due Date
                                    </label>

                                    <input type="text" name="due_date" id="due_date"
                                        value="{{ old(
                                            'due_date',
                                            $task->due_date
                                                ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d')
                                                : '',
                                        ) }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3
                                            transition-all duration-200
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('due_date') border-red-400 bg-red-50/50 @enderror">

                                    @error('due_date')
                                        <div
                                            class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                            {{ $message }}
                                        </div>
                                    @enderror
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="mt-8 flex flex-col gap-3 border-t border-gray-100 pt-6 sm:flex-row">
                        <button type="submit"
                            class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-8 py-3
                                font-semibold text-white shadow-lg shadow-blue-500/30
                                transition-all duration-300 hover:-translate-y-0.5 hover:bg-blue-700">

                            <i class="fa-solid fa-check mr-2"></i>

                            Update Task
                        </button>

                        <a href="{{ url()->previous() }}"
                            class="inline-flex items-center justify-center rounded-xl border border-gray-300
                                bg-white px-8 py-3 font-medium text-gray-700
                                transition-all duration-200 hover:bg-gray-50">

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
                // Dependency help panel
                const dependencyHelpToggle = document.getElementById('dependency-help-toggle');
                const dependencyHelpPanel = document.getElementById('dependency-help-panel');

                dependencyHelpToggle?.addEventListener('click', function() {
                    const isHidden = dependencyHelpPanel.classList.contains('hidden');

                    dependencyHelpPanel.classList.toggle('hidden');
                    dependencyHelpToggle.setAttribute(
                        'aria-expanded',
                        isHidden ? 'true' : 'false',
                    );

                    const label = dependencyHelpToggle.querySelector('span');

                    if (label) {
                        label.textContent = isHidden ? 'Tutup' : 'Panduan';
                    }
                });

                // Assignee dropdown
                function toggleDropdown() {
                    const dropdown = document.getElementById('assignee-dropdown');

                    dropdown.style.display =
                        dropdown.style.display === 'none' ? 'block' : 'none';
                }

                function updateLabel() {
                    const checkboxes = document.querySelectorAll(
                        'input[name="assignee_ids[]"]:checked',
                    );

                    const label = document.getElementById('assignee-label');

                    if (checkboxes.length === 0) {
                        label.textContent = 'Unassigned';

                        return;
                    }

                    const names = Array.from(checkboxes).map((checkbox) => {
                        return checkbox
                            .closest('label')
                            .querySelector('span')
                            .textContent
                            .trim();
                    });

                    label.textContent = names.join(', ');
                }

                window.toggleDropdown = toggleDropdown;
                window.updateLabel = updateLabel;

                document.addEventListener('click', function(event) {
                    const wrapper = document.getElementById('assignee-wrapper');

                    if (wrapper && !wrapper.contains(event.target)) {
                        const dropdown = document.getElementById('assignee-dropdown');

                        if (dropdown) {
                            dropdown.style.display = 'none';
                        }
                    }
                });

                // Flatpickr
                const duePicker = flatpickr('#due_date', {
                    dateFormat: 'Y-m-d',
                    defaultDate: @json(
                        old(
                            'due_date',
                            $task->due_date
                                ? \Carbon\Carbon::parse($task->due_date)->format('Y-m-d')
                                : '',
                        ),
                    ) || null,
                });

                const startPicker = flatpickr('#start_date', {
                    dateFormat: 'Y-m-d',
                    defaultDate: @json(
                        old(
                            'start_date',
                            $task->start_date
                                ? \Carbon\Carbon::parse($task->start_date)->format('Y-m-d')
                                : '',
                        ),
                    ) || null,

                    onChange: function(selectedDates, dateString) {
                        duePicker.set('minDate', dateString || null);

                        if (
                            selectedDates[0] &&
                            duePicker.selectedDates[0] &&
                            duePicker.selectedDates[0] < selectedDates[0]
                        ) {
                            duePicker.setDate(dateString);
                        }
                    },
                });

                if (startPicker.selectedDates[0]) {
                    duePicker.set('minDate', startPicker.input.value);
                }

                // Predecessor and dependency
                const predecessorSelect = document.getElementById('predecessor_id');
                const dependencySelect = document.getElementById('dependency_type');
                const currentPredecessor = @json(old('predecessor_id', $task->predecessor_id));
                const editProjectId = @json($task->project_id);
                const currentTaskId = @json($task->id);

                function addOneDay(dateString) {
                    const date = new Date(`${dateString}T00:00:00`);

                    date.setDate(date.getDate() + 1);

                    const year = date.getFullYear();
                    const month = String(date.getMonth() + 1).padStart(2, '0');
                    const day = String(date.getDate()).padStart(2, '0');

                    return `${year}-${month}-${day}`;
                }

                function applyDependencyConstraint() {
                    if (!predecessorSelect || !dependencySelect) {
                        return;
                    }

                    const selectedOption =
                        predecessorSelect.options[predecessorSelect.selectedIndex];

                    const dependency = dependencySelect.value;

                    startPicker.set('minDate', null);
                    duePicker.set(
                        'minDate',
                        startPicker.input.value || null,
                    );

                    if (!selectedOption?.value || !dependency) {
                        return;
                    }

                    const predecessorStart = selectedOption.dataset.start;
                    const predecessorDue = selectedOption.dataset.due;

                    switch (dependency) {
                        case 'FS':
                            if (predecessorDue) {
                                startPicker.set(
                                    'minDate',
                                    addOneDay(predecessorDue),
                                );
                            }

                            if (startPicker.input.value) {
                                duePicker.set(
                                    'minDate',
                                    startPicker.input.value,
                                );
                            }
                            break;

                        case 'SS':
                            if (predecessorStart) {
                                startPicker.set(
                                    'minDate',
                                    predecessorStart,
                                );
                            }
                            break;

                        case 'FF':
                            if (predecessorDue) {
                                duePicker.set(
                                    'minDate',
                                    predecessorDue,
                                );
                            }
                            break;

                        case 'SF':
                            if (predecessorStart) {
                                duePicker.set(
                                    'minDate',
                                    predecessorStart,
                                );
                            }
                            break;
                    }

                    const minimumStartDate = startPicker.config.minDate;

                    if (
                        minimumStartDate &&
                        startPicker.selectedDates[0] &&
                        startPicker.selectedDates[0] < minimumStartDate
                    ) {
                        startPicker.clear();
                        duePicker.clear();

                        return;
                    }

                    const minimumDueDate = duePicker.config.minDate;

                    if (
                        minimumDueDate &&
                        duePicker.selectedDates[0] &&
                        duePicker.selectedDates[0] < minimumDueDate
                    ) {
                        duePicker.clear();
                    }
                }

                predecessorSelect?.addEventListener(
                    'change',
                    applyDependencyConstraint,
                );

                dependencySelect?.addEventListener(
                    'change',
                    applyDependencyConstraint,
                );

                async function loadTasksEdit(projectId) {
                    if (!predecessorSelect) {
                        return;
                    }

                    predecessorSelect.innerHTML =
                        '<option value="">— Tidak ada —</option>';

                    if (!projectId) {
                        return;
                    }

                    try {
                        const response = await fetch(
                            `/projects/${projectId}/tasks-json`,
                        );

                        if (!response.ok) {
                            throw new Error('Gagal memuat task project.');
                        }

                        const tasks = await response.json();

                        tasks.forEach((task) => {
                            if (task.id == currentTaskId) {
                                return;
                            }

                            const option = new Option(
                                task.name,
                                task.id,
                                false,
                                task.id == currentPredecessor,
                            );

                            option.dataset.start = task.start_date;
                            option.dataset.due = task.due_date;

                            predecessorSelect.appendChild(option);
                        });

                        if (currentPredecessor) {
                            predecessorSelect.value = String(currentPredecessor);
                        }

                        applyDependencyConstraint();
                    } catch (error) {
                        console.error('Gagal load tasks:', error);
                    }
                }

                loadTasksEdit(editProjectId);
            });
        </script>
    @endpush

    @include('tasks.partials._scripts')
@endsection
