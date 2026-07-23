<div>
    <label for="project_template_id" class="mb-2 block text-sm font-semibold text-gray-800">
        Buat Project Dari
    </label>
    <select name="project_template_id" id="project_template_id"
        class="w-full rounded-xl border border-gray-300 px-4 py-3 transition-all duration-200 focus:border-indigo-500 focus:ring-2 focus:ring-indigo-500/50 @error('project_template_id') border-red-400 bg-red-50/50 @enderror">
        <option value="">Tanpa Template</option>
        @foreach ($projectTemplates as $projectTemplate)
            <option value="{{ $projectTemplate['id'] }}"
                data-preview-url="{{ route('project-templates.preview', ['projectTemplate' => $projectTemplate['id']]) }}"
                @selected($selectedProjectTemplateId === $projectTemplate['id'])>
                {{ $projectTemplate['category'] }} — {{ $projectTemplate['name'] }} (v{{ $projectTemplate['version'] }})
            </option>
        @endforeach
    </select>
    @error('project_template_id')
        <div class="mt-2 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-600">
            {{ $message }}
        </div>
    @enderror
    <p class="mt-2 text-xs leading-5 text-slate-500">
        Pilih template aktif untuk melihat hierarchy, beban relatif, dependency, dan estimasi timeline.
    </p>
</div>
