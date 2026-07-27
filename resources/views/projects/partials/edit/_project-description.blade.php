<div class="border-t border-gray-100"></div>

<section aria-labelledby="edit-project-description-title">
    <div class="mb-5 flex items-start gap-3">
        {{-- <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
            <i class="fa-solid fa-align-left"></i>
        </div> --}}
        <div>
            <h2 id="edit-project-description-title" class="text-base font-semibold text-gray-900">
                Project Description
            </h2>
            <p class="mt-0.5 text-sm text-gray-500">
                Perbarui ruang lingkup, tujuan, atau informasi penting project.
            </p>
        </div>
    </div>

    <label for="description" class="mb-2 block text-sm font-semibold text-gray-800">
        Description <span class="text-xs font-normal text-gray-400">(optional)</span>
    </label>
    <textarea id="description" name="description" rows="5"
        placeholder="Describe the project objectives, scope, and expected results..."
        class="w-full resize-none rounded-xl border border-gray-300 px-4 py-3 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('description') border-red-400 bg-red-50/50 @enderror">{{ old('description', $project->description) }}</textarea>
    @error('description')
        <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror
</section>
