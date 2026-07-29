<section class="mb-6 border-b border-slate-200 pb-5" aria-labelledby="workload-filter-title">
    <h2 id="workload-filter-title" class="sr-only">Filter monitoring</h2>

    <form method="GET" action="{{ route('overload.index') }}"
        class="grid gap-3 md:grid-cols-2 xl:grid-cols-12 xl:items-end"
        data-workload-filter-form>
        @if ($is_super_admin)
            <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 xl:col-span-2">
                Scope
                <select name="scope"
                    class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                    <option value="managed" @selected($filters['scope'] === 'managed')>Cakupan Saya</option>
                    <option value="all" @selected($filters['scope'] === 'all')>Semua Sistem</option>
                </select>
            </label>
        @endif

        <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 xl:col-span-2">
            Periode
            <select name="period"
                class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500"
                data-workload-period>
                <option value="next_7_days" @selected($filters['period'] === 'next_7_days')>7 Hari ke Depan</option>
                <option value="this_week" @selected($filters['period'] === 'this_week')>Minggu Ini</option>
                <option value="this_month" @selected($filters['period'] === 'this_month')>Bulan Ini</option>
                <option value="custom" @selected($filters['period'] === 'custom')>Rentang Kustom</option>
            </select>
        </label>

        <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 xl:col-span-2">
            Workspace
            <select name="workspace"
                class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                <option value="">Semua workspace</option>
                @foreach ($workspaces as $workspace)
                    <option value="{{ $workspace->id }}" @selected($filters['workspace'] === $workspace->id)>
                        {{ $workspace->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 xl:col-span-2">
            Project
            <select name="project"
                class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                <option value="">Semua project</option>
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected($filters['project'] === $project->id)>
                        {{ $project->name }}
                    </option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 xl:col-span-2">
            Level
            <select name="level"
                class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
                <option value="">Semua level</option>
                @foreach ($levels as $key => $level)
                    <option value="{{ $key }}" @selected($filters['level'] === $key)>{{ $level['label'] }}</option>
                @endforeach
            </select>
        </label>

        <label class="flex flex-col gap-1.5 text-sm font-medium text-slate-700 md:col-span-2 xl:col-span-4">
            Cari anggota
            <input type="search" name="search" value="{{ $filters['search'] }}"
                placeholder="Nama, email, atau employee ID"
                class="min-h-10 rounded-xl border-slate-300 text-sm placeholder:text-slate-400 focus:border-sky-500 focus:ring-sky-500">
        </label>

        <div class="hidden gap-3 md:col-span-2 xl:col-span-4" data-workload-custom-range>
            <label class="flex flex-1 flex-col gap-1.5 text-sm font-medium text-slate-700">
                Mulai
                <input type="date" name="start_date" value="{{ $filters['start_date'] }}"
                    class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
            </label>
            <label class="flex flex-1 flex-col gap-1.5 text-sm font-medium text-slate-700">
                Selesai
                <input type="date" name="end_date" value="{{ $filters['end_date'] }}"
                    class="min-h-10 rounded-xl border-slate-300 text-sm focus:border-sky-500 focus:ring-sky-500">
            </label>
        </div>

        <div class="flex items-center gap-2 md:col-span-2 xl:col-span-4">
            <button type="submit"
                class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 rounded-xl bg-slate-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 sm:flex-none">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Terapkan filter
            </button>
            <a href="{{ route('overload.index') }}"
                class="inline-flex min-h-10 flex-1 items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-slate-900 sm:flex-none">
                Reset
            </a>
        </div>
    </form>

    @if ($errors->any())
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700" role="alert">
            {{ $errors->first() }}
        </div>
    @endif
</section>
