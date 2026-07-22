<div class="overflow-hidden rounded-xl border border-slate-200 bg-white">
    <div class="border-b border-slate-200 px-4 py-3"><h3 class="font-semibold text-slate-800">Preview Jadwal Relatif</h3><p class="text-xs text-slate-500">Hari ke-1 menggunakan tanggal dasar 1 Januari 2000.</p></div>
    <div class="max-h-72 overflow-auto">
        <table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Task</th><th class="px-4 py-3">Mulai</th><th class="px-4 py-3">Selesai</th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($tasks as $task)
                    @php($itemSchedule = $schedule[$task->id] ?? null)
                    <tr><td class="px-4 py-3 font-medium text-slate-800">{{ $task->name }}</td><td class="px-4 py-3">{{ $itemSchedule ? 'Hari ke-'.($itemSchedule['start_date']->diffInDays(\Carbon\CarbonImmutable::parse('2000-01-01')) + 1) : '—' }}</td><td class="px-4 py-3">{{ $itemSchedule ? 'Hari ke-'.($itemSchedule['due_date']->diffInDays(\Carbon\CarbonImmutable::parse('2000-01-01')) + 1) : '—' }}</td></tr>
                @empty<tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">Belum ada jadwal.</td></tr>@endforelse
            </tbody>
        </table>
    </div>
</div>
