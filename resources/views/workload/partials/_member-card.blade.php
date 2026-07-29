<article class="p-4">
    <div class="flex min-w-0 items-center gap-3">
        @if ($member['profile_photo'])
            <img src="{{ asset('storage/'.$member['profile_photo']) }}" alt=""
                class="h-10 w-10 shrink-0 rounded-full object-cover">
        @else
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-sky-600 text-sm font-bold text-white"
                aria-hidden="true">
                {{ $member['initial'] }}
            </div>
        @endif
        <div class="min-w-0 flex-1">
            <h3 class="truncate font-semibold text-slate-900">{{ $member['name'] }}</h3>
            <p class="truncate text-xs text-slate-500">{{ $member['employee_id'] ?: $member['email'] }}</p>
        </div>
        <strong class="text-lg text-slate-900">{{ number_format($member['score'], 2) }}</strong>
    </div>

    <div class="mt-3 flex items-center justify-between gap-3">
        <span @class([
            'inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
            'bg-emerald-100 text-emerald-700 ring-emerald-600/20' => $member['level'] === 'normal',
            'bg-amber-100 text-amber-800 ring-amber-600/20' => $member['level'] === 'attention',
            'bg-orange-100 text-orange-800 ring-orange-600/20' => $member['level'] === 'high_risk',
            'bg-red-100 text-red-700 ring-red-600/20' => $member['level'] === 'critical',
        ])>
            {{ $member['level_label'] }}
        </span>
        <span class="text-xs text-slate-500">Skor Beban Tugas</span>
    </div>

    <dl class="mt-4 grid grid-cols-4 gap-2 border-y border-slate-100 py-3 text-center">
        <div>
            <dt class="text-[11px] text-slate-500">Task</dt>
            <dd class="text-sm font-semibold text-slate-800">{{ $member['contributing_task_count'] }}</dd>
        </div>
        <div>
            <dt class="text-[11px] text-slate-500">Project</dt>
            <dd class="text-sm font-semibold text-slate-800">{{ $member['contributing_project_count'] }}</dd>
        </div>
        <div>
            <dt class="text-[11px] text-slate-500">Overdue</dt>
            <dd class="text-sm font-semibold text-rose-700">{{ $member['overdue_count'] }}</dd>
        </div>
        <div>
            <dt class="text-[11px] text-slate-500">Unscheduled</dt>
            <dd class="text-sm font-semibold text-slate-700">{{ $member['unscheduled_count'] }}</dd>
        </div>
    </dl>

    <button type="button"
        class="mt-4 inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-xl border border-sky-200 px-4 py-2 text-sm font-semibold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
        data-workload-detail-open data-detail-url="{{ route('overload.members.show', $member['id']) }}"
        aria-controls="workload-member-detail-modal">
        Lihat Perhitungan
        <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
    </button>
</article>
