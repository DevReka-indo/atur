<section class="lg:col-span-2" aria-labelledby="template-preview-title" data-project-template-preview>
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-slate-50">
        <div class="border-b border-slate-200 bg-white px-4 py-4 sm:px-5">
            <div class="flex items-center gap-3">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="fa-solid fa-diagram-project"></i>
                </span>
                <div>
                    <h2 id="template-preview-title" class="text-sm font-semibold text-slate-900">Preview Project Template</h2>
                    <p class="text-xs text-slate-500">Preview bersifat read-only dan tidak mengubah tanggal pada form.</p>
                </div>
            </div>
        </div>

        <div class="p-4 sm:p-5">
            <div data-preview-state="empty">
                <div class="flex gap-3 rounded-xl border border-dashed border-slate-300 bg-white p-4">
                    <i class="fa-solid fa-list-check mt-0.5 text-slate-400"></i>
                    <div>
                        <p class="font-semibold text-slate-800">Tanpa Template</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">
                            Enam default task tetap dibuat seperti flow sebelumnya.
                        </p>
                    </div>
                </div>
            </div>

            <div class="hidden" data-preview-state="loading" role="status">
                <div class="flex items-center justify-center gap-3 rounded-xl border border-slate-200 bg-white p-8 text-sm text-slate-600">
                    <i class="fa-solid fa-spinner animate-spin text-indigo-600"></i>
                    <span>Memuat detail dan menghitung timeline template...</span>
                </div>
            </div>

            <div class="hidden" data-preview-state="error" role="alert">
                <div class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-red-700">
                    <i class="fa-solid fa-circle-exclamation mt-0.5"></i>
                    <div>
                        <p class="font-semibold">Preview tidak dapat dimuat</p>
                        <p class="mt-1 text-sm" data-preview-error-message>
                            Silakan periksa tanggal atau pilih kembali template. Form project tetap dapat digunakan.
                        </p>
                    </div>
                </div>
            </div>

            <div class="hidden space-y-5" data-preview-state="content">
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-lg font-bold text-slate-900" data-preview-name></h3>
                        <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-semibold text-green-700">Aktif</span>
                    </div>
                    <p class="mt-1 text-xs font-medium text-indigo-600" data-preview-meta></p>
                    <p class="mt-2 text-sm leading-6 text-slate-600" data-preview-description></p>
                </div>

                <dl class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                    @foreach ([
                        'tasks' => 'Seluruh Task',
                        'roots' => 'Root Task',
                        'leaves' => 'Leaf Task',
                        'levels' => 'Level',
                        'weight' => 'Total Beban',
                        'duration' => 'Durasi Kalender',
                    ] as $key => $label)
                        <div class="rounded-xl border border-slate-200 bg-white p-3">
                            <dt class="text-xs font-medium text-slate-500">{{ $label }}</dt>
                            <dd class="mt-1 text-lg font-bold text-slate-900" data-summary="{{ $key }}">—</dd>
                        </div>
                    @endforeach
                </dl>

                <div class="grid gap-3 md:grid-cols-3">
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <p class="text-xs font-medium text-slate-500">Project Mulai</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800" data-timeline="start">Belum diisi</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <p class="text-xs font-medium text-slate-500">Due Date Diminta</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800" data-timeline="requested">Belum diisi</p>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-white p-3">
                        <p class="text-xs font-medium text-slate-500">Estimasi Task Terakhir</p>
                        <p class="mt-1 text-sm font-semibold text-slate-800" data-timeline="estimated">Isi start date</p>
                    </div>
                </div>

                <div class="hidden rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                    data-timeline-warning>
                    <div class="flex gap-3">
                        <i class="fa-solid fa-triangle-exclamation mt-0.5"></i>
                        <p>
                            Task terakhir melewati due date yang diminta. Saat project dibuat, end date project akan
                            diperpanjang mengikuti estimasi task terakhir.
                        </p>
                    </div>
                </div>

                <div>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold text-slate-900">Hierarchy dan Timeline Task</h3>
                        <span class="text-xs text-slate-500">Maksimal 3 level</span>
                    </div>
                    <div class="space-y-2" data-preview-tree></div>
                </div>
            </div>
        </div>
    </div>
</section>
