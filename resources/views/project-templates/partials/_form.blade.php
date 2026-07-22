@php($editing = isset($template))
<div class="space-y-5">
    <div>
        <label for="project_template_category_id" class="mb-2 block text-sm font-semibold text-slate-700">Kategori</label>
        <select id="project_template_category_id" name="project_template_category_id" required class="w-full rounded-xl border-slate-300">
            <option value="">Pilih kategori</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((int) old('project_template_category_id', $template->project_template_category_id ?? 0) === $category->id)>{{ $category->name }}{{ $category->is_active ? '' : ' (Tidak aktif)' }}</option>
            @endforeach
        </select>
        @error('project_template_category_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Template</label>
        <input id="name" name="name" type="text" maxlength="255" required value="{{ old('name', $template->name ?? '') }}" class="w-full rounded-xl border-slate-300">
        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
        <textarea id="description" name="description" rows="4" class="w-full rounded-xl border-slate-300">{{ old('description', $template->description ?? '') }}</textarea>
        @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>
<div class="mt-6 flex gap-3 border-t border-slate-200 pt-5">
    <button class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700"><i class="fa-solid fa-floppy-disk"></i>{{ $editing ? 'Simpan Metadata' : 'Buat Template' }}</button>
    <a href="{{ route('project-templates.index') }}" class="rounded-xl border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Batal</a>
</div>
