<span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $template->isEffectivelyActive() ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
    <i class="fa-solid {{ $template->isEffectivelyActive() ? 'fa-circle-check' : 'fa-circle-pause' }}"></i>
    {{ $template->isEffectivelyActive() ? 'Aktif' : 'Tidak aktif' }}
</span>
