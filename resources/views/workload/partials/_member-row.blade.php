<tr class="transition hover:bg-slate-50">
    <td class="px-5 py-4">
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
        <div class="min-w-0">
            <h3 class="truncate font-semibold text-slate-900">{{ $member['name'] }}</h3>
            <p class="truncate text-xs text-slate-500">
                {{ $member['employee_id'] ?: $member['email'] }}
            </p>
            @if ($member['job_title'])
                <p class="truncate text-xs text-slate-400">{{ $member['job_title'] }}</p>
            @endif
        </div>
        </div>
    </td>
    <td class="px-5 py-4 font-semibold text-slate-900">{{ number_format($member['score'], 2) }}</td>
    <td class="px-5 py-4">
        <span @class([
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold ring-1 ring-inset',
                'bg-emerald-100 text-emerald-700 ring-emerald-600/20' => $member['level'] === 'normal',
                'bg-amber-100 text-amber-800 ring-amber-600/20' => $member['level'] === 'attention',
                'bg-orange-100 text-orange-800 ring-orange-600/20' => $member['level'] === 'high_risk',
                'bg-red-100 text-red-700 ring-red-600/20' => $member['level'] === 'critical',
            ])>
                <span @class([
                    'h-1.5 w-1.5 rounded-full',
                    'bg-emerald-500' => $member['level'] === 'normal',
                    'bg-amber-500' => $member['level'] === 'attention',
                    'bg-orange-500' => $member['level'] === 'high_risk',
                    'bg-red-500' => $member['level'] === 'critical',
                ]) aria-hidden="true"></span>
                {{ $member['level_label'] }}
        </span>
    </td>
    <td class="px-5 py-4 text-center font-semibold text-slate-800">{{ $member['contributing_task_count'] }}</td>
    <td class="px-5 py-4 text-center font-semibold text-slate-800">{{ $member['contributing_project_count'] }}</td>
    <td class="px-5 py-4 text-center font-semibold text-rose-700">{{ $member['overdue_count'] }}</td>
    <td class="px-5 py-4 text-center font-semibold text-slate-700">{{ $member['unscheduled_count'] }}</td>
    <td class="px-5 py-4 text-right">
        <button type="button"
            class="inline-flex min-h-9 items-center justify-center gap-2 rounded-lg border border-sky-200 px-3 py-2 text-xs font-semibold text-sky-700 transition hover:bg-sky-50 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2"
            data-workload-detail-open data-detail-url="{{ route('overload.members.show', $member['id']) }}"
            aria-controls="workload-member-detail-modal">
            Lihat Perhitungan
        </button>
    </td>
</tr>
