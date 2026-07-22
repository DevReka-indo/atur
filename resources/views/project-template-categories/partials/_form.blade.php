@php($editing = isset($category))

<div class="space-y-5">
    <div>
        <label for="name" class="mb-2 block text-sm font-semibold text-slate-700">Nama Kategori</label>
        <input id="name" name="name" type="text" required maxlength="255"
            value="{{ old('name', $category->name ?? '') }}"
            class="w-full rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">
        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="description" class="mb-2 block text-sm font-semibold text-slate-700">Deskripsi</label>
        <textarea id="description" name="description" rows="4"
            class="w-full rounded-xl border-slate-300 focus:border-sky-500 focus:ring-sky-500">{{ old('description', $category->description ?? '') }}</textarea>
        @error('description')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    @unless($editing)
        <label class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                class="rounded border-slate-300 text-sky-600 focus:ring-sky-500">
            <span class="text-sm font-medium text-slate-700">Aktifkan kategori setelah dibuat</span>
        </label>
    @endunless
</div>

<div class="mt-6 flex flex-wrap gap-3 border-t border-slate-200 pt-5">
    <button class="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-sky-700">
        <i class="fa-solid fa-floppy-disk"></i>
        {{ $editing ? 'Simpan Perubahan' : 'Buat Kategori' }}
    </button>
    <a href="{{ route('project-template-categories.index') }}"
        class="rounded-xl border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Batal</a>
</div>
