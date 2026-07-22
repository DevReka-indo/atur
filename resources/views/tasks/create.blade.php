@extends('layouts.app')

@section('title', $parentTask ? 'Tambah Subtask' : 'Create Task')

@section('content')
    <div class="fixed inset-0 -z-10 bg-gradient-to-br from-gray-50 to-gray-100/50"></div>

    <div class="w-4xl mx-auto px-4 pb-8 pt-2 sm:px-6 lg:px-8">

        {{-- Breadcrumb --}}
        <nav class="mb-6 flex items-center gap-2 text-sm text-gray-500">
            <a href="{{ route('dashboard') }}" class="transition-colors hover:text-indigo-600">
                Home
            </a>

            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

            <a href="{{ route('tasks.index') }}" class="transition-colors hover:text-indigo-600">
                Tasks
            </a>

            <i class="fa-solid fa-chevron-right text-xs text-gray-400"></i>

            <span class="font-medium text-gray-700">
                {{ $parentTask ? 'Tambah Subtask' : 'Create' }}
            </span>
        </nav>

        {{-- Header --}}
        <div class="mb-8">
            <h1
                class="bg-gradient-to-r from-gray-900 to-gray-600 bg-clip-text text-3xl font-bold text-transparent">
                {{ $parentTask ? 'Tambah Subtask' : 'Create Task' }}
            </h1>

            <p class="mt-2 text-gray-600">
                {{ $parentTask
                    ? 'Tambahkan pekerjaan turunan dengan bobot terhadap parent.'
                    : 'Add a new task and set the details of its execution.' }}
            </p>
        </div>

        {{-- Card --}}
        <div
            class="overflow-hidden rounded-2xl border border-gray-200/60 bg-white/90 shadow-xl backdrop-blur-sm">

            {{-- Accent Bar --}}
            <div class="h-1.5 bg-[#219ebc]"></div>

            <div class="p-6 sm:p-8">
                <form method="POST" action="{{ route('tasks.store') }}" class="space-y-6">
                    @csrf

                    @if ($parentTask)
                        <input type="hidden" name="parent_task_id" value="{{ $parentTask->id }}">
                    @endif

                    @if ($errors->any())
                        <div class="mb-4 rounded-lg border border-red-400 bg-red-100 px-4 py-3 text-red-700">
                            <ul class="list-inside list-disc space-y-1 text-sm">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @include('tasks.partials._parent-context', [
                        'parentTask' => $parentTask,
                        'parentDepth' => $parentDepth,
                        'usedSubtaskWeight' => $usedSubtaskWeight,
                        'remainingSubtaskWeight' => $remainingSubtaskWeight,
                    ])

                    {{-- Grid Layout --}}
                    <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">

                        {{-- Kolom Kiri: Task Details --}}
                        <div class="space-y-6">

                            {{-- Project --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Project
                                    <span class="text-red-500">*</span>
                                </label>

                                <select name="project_id"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('project_id') border-red-400 bg-red-50/50 @enderror"
                                    required {{ isset($project) ? 'disabled' : '' }}>

                                    <option value="">Select project</option>

                                    @foreach ($projects as $item)
                                        @php
                                            $selectedValue = old(
                                                'project_id',
                                                $project?->id ?? request('project_id'),
                                            );
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
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Task Name --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Task Name
                                    <span class="text-red-500">*</span>
                                </label>

                                <input type="text" name="name" value="{{ old('name') }}"
                                    placeholder="e.g. Design Homepage UI"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-3
                                        transition-all duration-200
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                        @error('name') border-red-400 bg-red-50/50 @enderror"
                                    required>

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
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50">{{ old('description') }}</textarea>
                            </div>

                            {{-- Assignee --}}
                            <div class="relative" id="assignee-wrapper">
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    PIC
                                </label>

                                <button type="button" id="assignee-trigger"
                                    class="flex w-full items-center justify-between rounded-xl border
                                        border-gray-300 bg-white px-4 py-3 text-left
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50">

                                    <div id="assignee-selected-tags" class="flex flex-wrap items-center gap-1.5">
                                        <span id="assignee-placeholder" class="text-gray-500">
                                            Select PIC
                                        </span>
                                    </div>

                                    <i id="assignee-chevron"
                                        class="fa-solid fa-chevron-down ml-2 text-xs text-gray-400
                                            transition-transform duration-200">
                                    </i>
                                </button>

                                <div id="assignee-dropdown"
                                    class="absolute z-50 mt-1 hidden max-h-60 w-full overflow-y-auto
                                        rounded-xl border border-gray-200 bg-white shadow-lg">

                                    @foreach ($assignees as $assignee)
                                        @php
                                            $hex = '#' . substr(md5($assignee->name), 0, 6);
                                            $initials = strtoupper(substr($assignee->name, 0, 1));
                                        @endphp

                                        <div class="assignee-option flex cursor-pointer items-center justify-between
                                                px-4 py-3 transition-colors hover:bg-indigo-50"
                                            data-id="{{ $assignee->id }}"
                                            data-name="{{ $assignee->name }}"
                                            data-initials="{{ $initials }}"
                                            data-color="{{ $hex }}">

                                            <div class="flex items-center gap-3">
                                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center
                                                        rounded-full text-sm font-medium"
                                                    style="background-color: {{ $hex }}22; color: {{ $hex }};">
                                                    {{ $initials }}
                                                </div>

                                                <span class="text-sm text-gray-700">
                                                    {{ $assignee->name }}
                                                </span>

                                                @if ($assignee->id === auth()->id())
                                                    <span
                                                        class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs
                                                            font-semibold text-indigo-600">
                                                        You
                                                    </span>
                                                @endif
                                            </div>

                                            <div
                                                class="assignee-checkbox flex h-5 w-5 flex-shrink-0 items-center
                                                    justify-center rounded border-2 border-gray-300
                                                    transition-colors">
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                {{-- Hidden Inputs --}}
                                <div id="assignee-hidden-inputs"></div>

                                @error('assignee_ids')
                                    <div
                                        class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>

                            {{-- Status --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Status
                                </label>

                                <select name="status"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-3
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">

                                    @foreach (['to_do', 'in_progress', 'review', 'completed', 'stopped', 'cancelled'] as $s)
                                        <option value="{{ $s }}"
                                            class="{{ $parentTask && $s !== 'to_do' ? 'js-subtask-status-option' : '' }}"
                                            {{ $parentTask && $s !== 'to_do' ? 'disabled' : '' }}
                                            {{ old('status', 'to_do') === $s ? 'selected' : '' }}>
                                            {{ str($s)->replace('_', ' ')->title() }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                        </div>

                        {{-- Kolom Kanan: Task Relations & Settings --}}
                        <div class="space-y-6">

                            {{-- Predecessor + Dependency Type --}}
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
                                                {{ old('dependency_type', 'FS') === 'FS' ? 'selected' : '' }}>
                                                FS — Finish to Start
                                            </option>

                                            <option value="SS"
                                                {{ old('dependency_type') === 'SS' ? 'selected' : '' }}>
                                                SS — Start to Start
                                            </option>

                                            <option value="FF"
                                                {{ old('dependency_type') === 'FF' ? 'selected' : '' }}>
                                                FF — Finish to Finish
                                            </option>

                                            <option value="SF"
                                                {{ old('dependency_type') === 'SF' ? 'selected' : '' }}>
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
                                                    yang sedang dibuat. Pilih tipe dependency untuk menentukan
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
                                                Task baru hanya dapat dimulai setelah predecessor selesai.
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
                                                Task baru dapat dimulai bersamaan atau setelah predecessor mulai.
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
                                                Task baru harus selesai bersamaan atau setelah predecessor selesai.
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
                                                Task baru harus selesai setelah predecessor mulai.
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
                                                Gunakan dependency hanya jika jadwal task memang bergantung pada
                                                task lain. Untuk task yang dapat berjalan mandiri, biarkan
                                                predecessor kosong.
                                            </span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            {{-- Priority --}}
                            <div>
                                <label class="mb-2 block text-sm font-semibold text-gray-800">
                                    Priority
                                </label>

                                <select name="priority"
                                    class="w-full rounded-xl border border-gray-300 px-4 py-3
                                        focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500">

                                    @foreach (['low', 'medium', 'high', 'urgent'] as $p)
                                        <option value="{{ $p }}"
                                            {{ old('priority', 'medium') === $p ? 'selected' : '' }}>
                                            {{ ucfirst($p) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            @include('tasks.partials._weight-readiness', [
                                'isSubtask' => $parentTask !== null,
                                'siblingWeightBase' => $usedSubtaskWeight,
                                'remainingSubtaskWeight' => $remainingSubtaskWeight,
                                'subtaskWeightValue' => null,
                                'remainingAfterInput' => max(
                                    0,
                                    $remainingSubtaskWeight -
                                        (float) old('subtask_weight_percentage', 0),
                                ),
                                'legacyWeight' => '1.00',
                                'rootWeightValue' => old('weight', '1.00'),
                                'statusUnlocked' => false,
                            ])

                            {{-- Dates --}}
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

                                {{-- Start Date --}}
                                <div>
                                    <label class="mb-2 block text-sm font-semibold text-gray-800">
                                        Start Date
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input type="text" id="start_date" name="start_date"
                                        value="{{ old('start_date') }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('start_date') border-red-400 bg-red-50/50 @enderror"
                                        required>

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
                                        <span class="text-red-500">*</span>
                                    </label>

                                    <input type="text" id="due_date" name="due_date"
                                        value="{{ old('due_date') }}"
                                        placeholder="YYYY-MM-DD"
                                        class="w-full rounded-xl border border-gray-300 px-4 py-3
                                            focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50
                                            @error('due_date') border-red-400 bg-red-50/50 @enderror"
                                        required>

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

                            {{ $parentTask ? 'Tambah Subtask' : 'Create Task' }}
                        </button>

                        <a href="{{ $parentTask
                            ? route('tasks.show', $parentTask->token)
                            : route('tasks.index') }}"
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
                    dependencyHelpToggle.setAttribute('aria-expanded', isHidden ? 'true' : 'false');

                    const label = dependencyHelpToggle.querySelector('span');

                    if (label) {
                        label.textContent = isHidden ? 'Tutup' : 'Panduan';
                    }
                });

                // Assignee dropdown
                const trigger = document.getElementById('assignee-trigger');
                const dropdown = document.getElementById('assignee-dropdown');
                const chevron = document.getElementById('assignee-chevron');
                const tagsBox = document.getElementById('assignee-selected-tags');
                const placeholder = document.getElementById('assignee-placeholder');
                const hiddenBox = document.getElementById('assignee-hidden-inputs');
                const options = document.querySelectorAll('.assignee-option');
                const selected = {};

                const oldIds = @json(old('assignee_ids', []));

                oldIds.forEach((id) => {
                    const element = document.querySelector(
                        `.assignee-option[data-id="${id}"]`,
                    );

                    if (!element) {
                        return;
                    }

                    selected[id] = {
                        id,
                        name: element.dataset.name,
                        initials: element.dataset.initials,
                        color: element.dataset.color,
                    };
                });

                trigger?.addEventListener('click', () => {
                    const isOpen = !dropdown.classList.contains('hidden');

                    dropdown.classList.toggle('hidden');
                    chevron.style.transform = isOpen
                        ? 'rotate(0deg)'
                        : 'rotate(180deg)';
                });

                document.addEventListener('click', (event) => {
                    const wrapper = document.getElementById('assignee-wrapper');

                    if (wrapper && !wrapper.contains(event.target)) {
                        dropdown?.classList.add('hidden');

                        if (chevron) {
                            chevron.style.transform = 'rotate(0deg)';
                        }
                    }
                });

                options.forEach((option) => {
                    option.addEventListener('click', function() {
                        const id = this.dataset.id;

                        if (selected[id]) {
                            delete selected[id];
                        } else {
                            selected[id] = {
                                id,
                                name: this.dataset.name,
                                initials: this.dataset.initials,
                                color: this.dataset.color,
                            };
                        }

                        render();
                    });
                });

                function render() {
                    document.querySelectorAll('.assignee-option').forEach((option) => {
                        const id = option.dataset.id;
                        const checkbox = option.querySelector('.assignee-checkbox');

                        if (!checkbox) {
                            return;
                        }

                        if (selected[id]) {
                            option.classList.add('bg-indigo-50');
                            checkbox.classList.add('bg-indigo-600', 'border-indigo-600');
                            checkbox.classList.remove('border-gray-300');
                            checkbox.innerHTML =
                                '<i class="fa-solid fa-check text-white" style="font-size: 10px"></i>';

                            return;
                        }

                        option.classList.remove('bg-indigo-50');
                        checkbox.classList.remove('bg-indigo-600', 'border-indigo-600');
                        checkbox.classList.add('border-gray-300');
                        checkbox.innerHTML = '';
                    });

                    tagsBox.innerHTML = '';
                    hiddenBox.innerHTML = '';

                    const ids = Object.keys(selected);

                    if (ids.length === 0) {
                        tagsBox.appendChild(placeholder);
                        placeholder.style.display = 'inline';

                        return;
                    }

                    placeholder.style.display = 'none';

                    ids.forEach((id) => {
                        const person = selected[id];
                        const tag = document.createElement('span');

                        tag.className =
                            'inline-flex items-center gap-1.5 rounded-lg bg-indigo-100 px-2 py-0.5 text-sm text-indigo-700';

                        tag.innerHTML = `
                            ${person.name}
                            <button
                                type="button"
                                data-remove="${id}"
                                class="font-bold leading-none text-indigo-400 hover:text-indigo-700"
                            >
                                ×
                            </button>
                        `;

                        tag.querySelector('button')?.addEventListener('click', function(event) {
                            event.stopPropagation();

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

                render();

                // Project and predecessor loader
                const projectSelect = document.querySelector('select[name="project_id"]');
                const hiddenProjectInput = document.querySelector(
                    'input[type="hidden"][name="project_id"]',
                );
                const predecessorSelect = document.getElementById('predecessor_id');
                const dependencySelect = document.getElementById('dependency_type');

                // Flatpickr instances
                const duePicker = flatpickr('#due_date', {
                    dateFormat: 'Y-m-d',
                    defaultDate: '{{ old('due_date') }}' || null,
                });

                const startPicker = flatpickr('#start_date', {
                    dateFormat: 'Y-m-d',
                    defaultDate: '{{ old('start_date') }}' || null,

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

                function addOneDay(dateString) {
                    const date = new Date(dateString);

                    date.setDate(date.getDate() + 1);

                    return date.toISOString().split('T')[0];
                }

                function applyDependencyConstraint() {
                    if (!predecessorSelect || !dependencySelect) {
                        return;
                    }

                    const selectedOption =
                        predecessorSelect.options[predecessorSelect.selectedIndex];

                    const dependency = dependencySelect.value;

                    startPicker.set('minDate', null);
                    duePicker.set('minDate', null);

                    if (!selectedOption?.value || !dependency) {
                        if (startPicker.input.value) {
                            duePicker.set('minDate', startPicker.input.value);
                        }

                        return;
                    }

                    const predecessorStart = selectedOption.dataset.start;
                    const predecessorDue = selectedOption.dataset.due;

                    switch (dependency) {
                        case 'FS':
                            if (predecessorDue) {
                                startPicker.set('minDate', addOneDay(predecessorDue));
                            }

                            if (startPicker.selectedDates[0]) {
                                duePicker.set('minDate', startPicker.input.value);
                            }
                            break;

                        case 'SS':
                            if (predecessorStart) {
                                startPicker.set('minDate', predecessorStart);
                            }
                            break;

                        case 'FF':
                            if (predecessorDue) {
                                duePicker.set('minDate', predecessorDue);
                            }
                            break;

                        case 'SF':
                            if (predecessorStart) {
                                duePicker.set('minDate', predecessorStart);
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

                predecessorSelect?.addEventListener('change', applyDependencyConstraint);
                dependencySelect?.addEventListener('change', applyDependencyConstraint);

                async function loadTasks(projectId) {
                    if (!predecessorSelect) {
                        return;
                    }

                    predecessorSelect.innerHTML =
                        '<option value="">— Tidak ada —</option>';

                    if (!projectId) {
                        return;
                    }

                    try {
                        const taskResponse = await fetch(
                            `/projects/${projectId}/tasks-json`,
                        );

                        if (!taskResponse.ok) {
                            throw new Error('Gagal memuat task project.');
                        }

                        const taskData = await taskResponse.json();

                        taskData.forEach((task) => {
                            const option = new Option(task.name, task.id);

                            option.dataset.start = task.start_date;
                            option.dataset.due = task.due_date;

                            predecessorSelect.appendChild(option);
                        });

                        const oldPredecessor = "{{ old('predecessor_id') }}";

                        if (oldPredecessor) {
                            predecessorSelect.value = oldPredecessor;
                        }

                        const assigneeResponse = await fetch(
                            `/projects/${projectId}/assignees-json`,
                        );

                        if (!assigneeResponse.ok) {
                            throw new Error('Gagal memuat PIC project.');
                        }

                        const assigneeData = await assigneeResponse.json();
                        const assigneeDropdown =
                            document.getElementById('assignee-dropdown');

                        assigneeDropdown
                            ?.querySelectorAll('.assignee-option')
                            .forEach((element) => element.remove());

                        Object.keys(selected).forEach((key) => {
                            delete selected[key];
                        });

                        assigneeData.forEach((user) => {
                            const isCurrentUser = user.id == {{ Auth::id() }};
                            const option = document.createElement('div');

                            option.className =
                                'assignee-option flex cursor-pointer items-center justify-between px-4 py-3 transition-colors hover:bg-indigo-50';

                            option.dataset.id = user.id;
                            option.dataset.name = user.name;
                            option.dataset.initials =
                                user.name.charAt(0).toUpperCase();
                            option.dataset.color = '#6366f1';

                            const avatarHtml = user.profile_photo
                                ? `
                                    <img
                                        src="/storage/${user.profile_photo}"
                                        alt="${user.name}"
                                        class="h-8 w-8 flex-shrink-0 rounded-full object-cover"
                                    >
                                `
                                : `
                                    <div
                                        class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full text-sm font-medium"
                                        style="background-color: #6366f122; color: #6366f1;"
                                    >
                                        ${user.name.charAt(0).toUpperCase()}
                                    </div>
                                `;

                            option.innerHTML = `
                                <div class="flex items-center gap-3">
                                    ${avatarHtml}

                                    <span class="text-sm text-gray-700">
                                        ${user.name}
                                    </span>

                                    ${isCurrentUser
                                        ? `
                                            <span class="rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-600">
                                                You
                                            </span>
                                        `
                                        : ''}
                                </div>

                                <div class="assignee-checkbox flex h-5 w-5 flex-shrink-0 items-center justify-center rounded border-2 border-gray-300 transition-colors">
                                </div>
                            `;

                            option.addEventListener('click', function() {
                                const id = this.dataset.id;

                                if (selected[id]) {
                                    delete selected[id];
                                } else {
                                    selected[id] = {
                                        id,
                                        name: this.dataset.name,
                                        initials: this.dataset.initials,
                                        color: this.dataset.color,
                                    };
                                }

                                render();
                            });

                            assigneeDropdown?.appendChild(option);
                        });

                        render();
                        applyDependencyConstraint();
                    } catch (error) {
                        console.error('Gagal load data:', error);
                    }
                }

                projectSelect?.addEventListener('change', (event) => {
                    loadTasks(event.target.value);
                });

                const initialProject =
                    projectSelect?.value || hiddenProjectInput?.value;

                if (initialProject) {
                    loadTasks(initialProject);
                }
            });
        </script>
    @endpush

    @include('tasks.partials._scripts')
@endsection
