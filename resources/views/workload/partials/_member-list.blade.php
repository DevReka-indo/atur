<section class="overflow-hidden rounded-2xl border border-slate-200 bg-white"
    aria-labelledby="workload-member-list-title">
    <div class="border-b border-slate-200 px-5 py-4">
        <h2 id="workload-member-list-title" class="font-semibold text-slate-900">Distribusi anggota</h2>
        <p class="mt-1 text-xs text-slate-500">
            Setiap anggota tampil satu kali, termasuk kontribusi dari seluruh project dalam scope terpilih.
        </p>
    </div>

    <div class="hidden overflow-x-auto lg:block" data-workload-desktop-table>
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-sky-100 text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-5 py-4">Anggota</th>
                    <th class="px-5 py-4">Skor Beban Tugas</th>
                    <th class="px-5 py-4">Level</th>
                    <th class="px-5 py-4 text-center">Task Aktif</th>
                    <th class="px-5 py-4 text-center">Shared Project</th>
                    <th class="px-5 py-4 text-center">Overdue</th>
                    <th class="px-5 py-4 text-center">Unscheduled</th>
                    <th class="px-5 py-4 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($members as $member)
                    @include('workload.partials._member-row', ['member' => $member])
                @empty
                    <tr>
                        <td colspan="8">
                            @include('workload.partials._empty-state')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="divide-y divide-slate-100 lg:hidden" data-workload-mobile-list>
        @forelse ($members as $member)
            @include('workload.partials._member-card', ['member' => $member])
        @empty
            @include('workload.partials._empty-state')
        @endforelse
    </div>

    @if ($members->hasPages())
        <div class="border-t border-slate-200 px-5 py-4">
            {{ $members->onEachSide(1)->links() }}
        </div>
    @endif
</section>
