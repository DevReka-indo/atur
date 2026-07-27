<section aria-labelledby="project-information-title">
    <div class="mb-5 flex items-start gap-3">
        {{-- <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-100 text-sky-700">
            <i class="fa-solid fa-diagram-project"></i>
        </div> --}}
        <div>
            <h2 id="project-information-title" class="text-base font-semibold text-gray-900">Project Information</h2>
            <p class="mt-0.5 text-sm text-gray-500">Tentukan workspace dan informasi utama project.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            <label for="workspace_id" class="mb-2 block text-sm font-semibold text-gray-800">
                Workspace <span class="text-red-500">*</span>
            </label>

            @if (request('workspace_id') && $workspaces->firstWhere('id', request('workspace_id')))
                @php
                    $lockedWorkspace = $workspaces->firstWhere('id', request('workspace_id'));
                @endphp
                <input type="hidden" name="workspace_id" value="{{ $lockedWorkspace->id }}">
                <div class="flex w-full items-center gap-3 rounded-xl border border-gray-300 bg-gray-50 px-4 py-3 text-gray-700">
                    <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-white text-sky-700 shadow-sm">
                        <i class="fa-solid fa-layer-group text-sm"></i>
                    </div>
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold">{{ $lockedWorkspace->name }}</p>
                        <p class="text-xs text-gray-500">Workspace dipilih dari halaman sebelumnya</p>
                    </div>
                </div>
            @else
                <select id="workspace_id" name="workspace_id" required
                    class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('workspace_id') border-red-400 bg-red-50/50 @enderror">
                    <option value="">Select workspace</option>
                    @foreach ($workspaces as $workspace)
                        <option value="{{ $workspace->id }}" @selected((string) old('workspace_id') === (string) $workspace->id)>
                            {{ $workspace->name }}
                        </option>
                    @endforeach
                </select>
                @error('workspace_id')
                    <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                        {{ $message }}
                    </div>
                @enderror
            @endif
        </div>

        <div>
            <label for="name" class="mb-2 block text-sm font-semibold text-gray-800">
                Project Name <span class="text-red-500">*</span>
            </label>
            <input type="text" id="name" name="name" value="{{ old('name') }}" placeholder="e.g. Website Redesign"
                required maxlength="255" autofocus
                class="w-full rounded-xl border border-gray-300 px-4 py-3 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('name') border-red-400 bg-red-50/50 @enderror">
            @error('name')
                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div class="lg:col-span-2">
            <label for="status" class="mb-2 block text-sm font-semibold text-gray-800">
                Initial Status <span class="text-red-500">*</span>
            </label>
            <select id="status" name="status" required
                class="w-full rounded-xl border border-gray-300 bg-white px-4 py-3 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('status') border-red-400 bg-red-50/50 @enderror">
                @foreach ([
                    'planning' => 'Planning',
                    'active' => 'Active',
                    'on_hold' => 'On Hold',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                    'urgent' => 'Urgent',
                ] as $value => $label)
                    <option value="{{ $value }}" @selected(old('status', 'planning') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-2 text-xs leading-relaxed text-gray-500">
                Gunakan status Planning untuk project yang masih berada pada tahap persiapan.
            </p>
            @error('status')
                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</section>

<div class="border-t border-gray-100"></div>
