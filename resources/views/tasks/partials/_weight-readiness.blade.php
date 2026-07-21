@if ($isSubtask)
    <div class="js-subtask-weight-panel" data-base-weight="{{ $siblingWeightBase }}"
        data-capacity="{{ $remainingSubtaskWeight }}" data-status-unlocked="{{ $statusUnlocked ? '1' : '0' }}">
        <label class="block text-sm font-semibold text-gray-800 mb-2" for="subtask_weight_percentage">
            Bobot terhadap parent <span class="text-red-500">*</span>
        </label>
        <div class="relative">
            <input id="subtask_weight_percentage" type="number" name="subtask_weight_percentage" step="0.01"
                min="0.01" max="{{ $remainingSubtaskWeight }}"
                value="{{ old('subtask_weight_percentage', $subtaskWeightValue) }}"
                class="w-full rounded-xl border border-gray-300 px-4 py-3 pr-10 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('subtask_weight_percentage') border-red-400 bg-red-50/50 @enderror"
                required>
            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-gray-500">%</span>
        </div>
        <div class="mt-2 flex flex-wrap items-center justify-between gap-2 text-xs">
            <p class="text-gray-500">
                Sibling lain: <span class="font-semibold text-gray-700">{{ number_format($siblingWeightBase, 2) }}%</span>
            </p>
            <p class="text-gray-500">
                Sisa setelah input: <span class="js-remaining-weight font-semibold text-indigo-700">{{ number_format($remainingAfterInput, 2) }}%</span>
            </p>
        </div>
        <p class="js-weight-warning mt-2 hidden rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800"></p>
        @error('subtask_weight_percentage')
            <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                {{ $message }}
            </div>
        @enderror
        <input type="hidden" name="weight" value="{{ $legacyWeight }}">
    </div>
@else
    <div>
        <label class="block text-sm font-semibold text-gray-800 mb-2">
            Weight <span class="text-red-500">*</span>
        </label>
        <input type="number" name="weight" step="0.01" min="0.01" value="{{ $rootWeightValue }}"
            class="w-full rounded-xl border border-gray-300 px-4 py-3 transition focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('weight') border-red-400 bg-red-50/50 @enderror"
            required>
        @error('weight')
            <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                {{ $message }}
            </div>
        @enderror
    </div>
@endif
