<div class="border-t border-gray-100"></div>

<section aria-labelledby="project-timeline-title">
    <div class="mb-5 flex items-start gap-3">
        {{-- <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
            <i class="fa-solid fa-calendar-days"></i>
        </div> --}}
        <div>
            <h2 id="project-timeline-title" class="text-base font-semibold text-gray-900">Project Timeline</h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Tentukan rentang waktu awal project. Timeline dapat diperpanjang mengikuti template.
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <label for="start_date" class="mb-2 block text-sm font-semibold text-gray-800">
                Start Date <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                    <i class="fa-regular fa-calendar"></i>
                </div>
                <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}" required
                    class="w-full rounded-xl border border-gray-300 py-3 pl-11 pr-4 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('start_date') border-red-400 bg-red-50/50 @enderror">
            </div>
            <p class="mt-2 text-xs text-gray-500">Tanggal dimulainya project.</p>
            @error('start_date')
                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $message }}
                </div>
            @enderror
        </div>

        <div>
            <label for="due_date" class="mb-2 block text-sm font-semibold text-gray-800">
                Due Date <span class="text-red-500">*</span>
            </label>
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-gray-400">
                    <i class="fa-regular fa-calendar-check"></i>
                </div>
                <input type="date" id="due_date" name="due_date" value="{{ old('due_date') }}"
                    min="{{ old('start_date') }}" required
                    class="w-full rounded-xl border border-gray-300 py-3 pl-11 pr-4 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('due_date') border-red-400 bg-red-50/50 @enderror">
            </div>
            <p class="mt-2 text-xs text-gray-500">Target awal penyelesaian project.</p>
            @error('due_date')
                <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <div class="mt-4 flex items-start gap-2 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <i class="fa-solid fa-circle-info mt-0.5 shrink-0"></i>
        <p class="leading-relaxed">
            Jika task terakhir dari template melewati Due Date, sistem akan memperpanjang timeline project secara otomatis.
        </p>
    </div>
</section>
