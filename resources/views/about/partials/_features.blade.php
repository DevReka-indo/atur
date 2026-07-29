@php
    $features = [
        ['icon' => 'fa-layer-group', 'title' => 'Workspace', 'description' => 'Kelola anggota, project, dan aktivitas tim dalam satu ruang kerja.', 'classes' => 'bg-indigo-50 text-indigo-700'],
        ['icon' => 'fa-list-check', 'title' => 'Projects & Tasks', 'description' => 'Susun project, task, subtask, dependency, dan progres pekerjaan.', 'classes' => 'bg-blue-50 text-blue-700'],
        ['icon' => 'fa-chart-gantt', 'title' => 'Gantt Chart', 'description' => 'Visualisasikan jadwal dan keterkaitan pekerjaan secara terstruktur.', 'classes' => 'bg-violet-50 text-violet-700'],
        ['icon' => 'fa-shield-halved', 'title' => 'Roles & Access', 'description' => 'Atur peran dan izin akses sesuai tanggung jawab pengguna.', 'classes' => 'bg-amber-50 text-amber-700'],
        ['icon' => 'fa-bell', 'title' => 'Notifications', 'description' => 'Pantau assignment, perubahan status, mention, dan tenggat waktu.', 'classes' => 'bg-sky-50 text-sky-700'],
        ['icon' => 'fa-user-plus', 'title' => 'Invitations', 'description' => 'Undang anggota terdaftar atau baru ke dalam workspace.', 'classes' => 'bg-emerald-50 text-emerald-700'],
        ['icon' => 'fa-clipboard-check', 'title' => 'My Tasks', 'description' => 'Kelola task personal melalui Kanban dan Gantt pribadi.', 'classes' => 'bg-cyan-50 text-cyan-700'],
        ['icon' => 'fa-comments', 'title' => 'Project Discussions', 'description' => 'Diskusikan pekerjaan project dengan thread, message, dan mention.', 'classes' => 'bg-teal-50 text-teal-700'],
        ['icon' => 'fa-message', 'title' => 'Workspace Chat', 'description' => 'Berkomunikasi langsung dengan seluruh anggota workspace.', 'classes' => 'bg-purple-50 text-purple-700'],
        ['icon' => 'fa-gauge-high', 'title' => 'Workload / Overload Monitoring', 'description' => 'Pantau distribusi beban kerja dan indikasi overload anggota.', 'classes' => 'bg-orange-50 text-orange-700'],
    ];
@endphp

<section aria-labelledby="features-title">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-blue-700">Kapabilitas Platform</p>
            <h2 id="features-title" class="mt-2 text-2xl font-bold tracking-tight text-slate-950">Fitur Utama</h2>
        </div>
        <p class="max-w-xl text-sm leading-6 text-slate-600">
            Perangkat kerja terintegrasi untuk perencanaan, kolaborasi, kontrol akses, dan pemantauan project.
        </p>
    </div>

    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @foreach ($features as $feature)
            <article class="group rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-200 hover:shadow-md">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl {{ $feature['classes'] }}">
                    <i class="fa-solid {{ $feature['icon'] }}" aria-hidden="true"></i>
                </span>
                <h3 class="mt-4 text-base font-semibold text-slate-900">{{ $feature['title'] }}</h3>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $feature['description'] }}</p>
            </article>
        @endforeach
    </div>
</section>
