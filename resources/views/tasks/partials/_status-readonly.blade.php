<div>
    <label class="block text-sm font-semibold text-gray-800 mb-2">Status</label>
    <div class="rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-3">
        <div class="flex flex-wrap items-center justify-between gap-2">
            <span class="font-semibold text-indigo-900">
                {{ str($task->status)->replace('_', ' ')->title() }}
            </span>
            <span class="rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-indigo-700">
                Status mengikuti subtask
            </span>
        </div>
        <p class="mt-2 text-xs text-indigo-700">
            Status task utama dihitung otomatis dari status seluruh subtask.
        </p>
    </div>
    <input type="hidden" name="status" value="{{ $task->status }}">
    @error('status')
        <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror
</div>
