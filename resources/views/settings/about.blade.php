@extends('layouts.app')

@section('title', 'About')

@section('content')
    <div class="fixed inset-0 bg-gradient-to-br from-gray-50 to-gray-100/50 -z-10"></div>

    <div class="max-w-8xl space-y-6">

        {{-- Header --}}
        <div class="space-y-1">
            <h1 class="text-4xl font-semibold text-slate-800 tracking-tight">About the Application</h1>
            <p class="text-slate-500">Information System and Application details.</p>
        </div>

        {{-- Informasi Aplikasi --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Informasi Aplikasi</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                <div>
                    <p class="text-xs text-slate-400 mb-1">Application</p>
                    <p class="text-sm font-semibold text-slate-800">ATUR — Management Proyek</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">Versi</p>
                    <span
                        class="inline-block text-xs font-semibold bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg">v1.1</span>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">Environment</p>
                    <span
                        class="inline-block text-xs font-semibold bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg">Production</span>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">Support</p>
                    <p class="text-sm font-medium text-slate-700">ATUR2026@gmail.com</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">Dibuat</p>
                    <p class="text-sm font-medium text-slate-700">2026</p>
                </div>
                <div>
                    <p class="text-xs text-slate-400 mb-1">Lisensi</p>
                    <p class="text-sm font-medium text-slate-700">Internal Use</p>
                </div>
            </div>
        </div>

        {{-- Fitur Utama --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Fitur Utama</p>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                @php
                    $features = [
                        [
                            'icon' => 'fa-layer-group',
                            'label' => 'Workspace',
                            'desc' => 'Kelola tim & proyek dalam satu wadah',
                            'color' => 'text-indigo-500 bg-indigo-50',
                        ],
                        [
                            'icon' => 'fa-diagram-project',
                            'label' => 'Project & Tasks',
                            'desc' => 'Pantau progres pekerjaan real-time',
                            'color' => 'text-blue-500 bg-blue-50',
                        ],
                        [
                            'icon' => 'fa-chart-gantt',
                            'label' => 'Gantt Chart',
                            'desc' => 'Visualisasi timeline proyek',
                            'color' => 'text-violet-500 bg-violet-50',
                        ],
                        [
                            'icon' => 'fa-shield-halved',
                            'label' => 'Role & Akses',
                            'desc' => 'Owner, Admin, Member, Viewer',
                            'color' => 'text-amber-500 bg-amber-50',
                        ],
                        [
                            'icon' => 'fa-bell',
                            'label' => 'Notifikasi',
                            'desc' => 'Pemberitahuan aktivitas tim',
                            'color' => 'text-rose-500 bg-rose-50',
                        ],
                        [
                            'icon' => 'fa-user-plus',
                            'label' => 'Undangan',
                            'desc' => 'Invite via email atau link',
                            'color' => 'text-emerald-500 bg-emerald-50',
                        ],
                        [
                            'icon' => 'fa-list-check',
                            'label' => 'My Tasks',
                            'desc' => 'Kelola tugas pribadi tiap anggota',
                            'color' => 'text-sky-500 bg-sky-50',
                        ],
                        [
                            'icon' => 'fa-comments',
                            'label' => 'Discussion',
                            'desc' => 'Diskusi & komentar antar anggota',
                            'color' => 'text-teal-500 bg-teal-50',
                        ],
                        [
                            'icon' => 'fa-fire-flame-curved',
                            'label' => 'Overload',
                            'desc' => 'Monitoring beban kerja anggota',
                            'color' => 'text-orange-500 bg-orange-50',
                        ],
                    ];
                @endphp
                @foreach ($features as $f)
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50">
                        <div class="w-8 h-8 rounded-lg {{ $f['color'] }} flex items-center justify-center flex-shrink-0">
                            <i class="fa-solid {{ $f['icon'] }} text-sm"></i>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-800">{{ $f['label'] }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $f['desc'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tim Pengembang
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Tim Pengembang</p>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                @php
                    $team = [
                        ['name' => 'Nama 1', 'role' => 'di isi bio sek', 'color' => 'bg-indigo-100 text-indigo-700'],
                        ['name' => 'Nama 2', 'role' => 'di isi bio sek', 'color' => 'bg-emerald-100 text-emerald-700'],
                        ['name' => 'Nama 3', 'role' => 'di isi bio sek', 'color' => 'bg-amber-100 text-amber-700'],
                        ['name' => 'Nama 4', 'role' => 'di isi bio sek', 'color' => 'bg-rose-100 text-rose-700'],
                    ];
                @endphp
                @foreach ($team as $member)
                    <div class="flex flex-col items-center text-center p-4 rounded-xl bg-slate-50">
                        <div
                            class="w-12 h-12 rounded-full {{ $member['color'] }} flex items-center justify-center font-semibold text-sm mb-3">
                            {{ strtoupper(substr($member['name'], 0, 1)) }}
                        </div>
                        <p class="text-sm font-semibold text-slate-800">{{ $member['name'] }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $member['role'] }}</p>
                    </div>
                @endforeach
            </div>
        </div> --}}


            {{-- Kebijakan Privasi --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest mb-4">Kebijakan Privasi</p>

                <div class="text-xs text-slate-400 flex justify-end mb-4">
                    <span>ATUR — PT Rekaindo</span>
                </div>

                <div class="space-y-2" x-data>
                    @php
                        $policies = [
                            [
                                'icon'    => 'fa-file-lines',
                                'title'   => '1. Ketentuan Umum & Ruang Lingkup',
                                'badge'   => 'Umum',
                                'color'   => 'bg-indigo-50 text-indigo-600',
                                'badge_c' => 'bg-indigo-50 text-indigo-700',
                                'content' => '<p>Kebijakan ini mengikat seluruh Pengguna sistem ATUR berdasarkan:</p><ul class="list-disc pl-4 mt-1 space-y-1"><li>UU No. 27 Tahun 2022 tentang Perlindungan Data Pribadi (UU PDP)</li><li>UU No. 11 Tahun 2008 jo. No. 19 Tahun 2016 tentang ITE</li><li>PP No. 71 Tahun 2019 tentang Penyelenggaraan Sistem Elektronik</li></ul>',
                            ],
                            [
                                'icon'    => 'fa-database',
                                'title'   => '2. Data Pribadi yang Dikumpulkan',
                                'badge'   => 'Data',
                                'color'   => 'bg-emerald-50 text-emerald-600',
                                'badge_c' => 'bg-emerald-50 text-emerald-700',
                                'content' => '<ul class="list-disc pl-4 space-y-1"><li><strong>Identitas:</strong> nama lengkap, email, username</li><li><strong>Aktivitas:</strong> log aktivitas, riwayat tugas, komentar</li><li><strong>Teknis:</strong> alamat IP, perangkat, browser, waktu akses</li><li><strong>Konten:</strong> data proyek dan lampiran yang diunggah</li></ul><p class="mt-2 text-emerald-700 bg-emerald-50 rounded-lg px-3 py-2">Sistem tidak mengumpulkan data keuangan, biometrik, atau kesehatan.</p>',
                            ],
                            [
                                'icon'    => 'fa-bullseye',
                                'title'   => '3. Tujuan Penggunaan Data',
                                'badge'   => 'Tujuan',
                                'color'   => 'bg-amber-50 text-amber-600',
                                'badge_c' => 'bg-amber-50 text-amber-700',
                                'content' => '<ul class="list-disc pl-4 space-y-1"><li>Autentikasi dan pengelolaan akun</li><li>Menampilkan dan mengelola konten proyek</li><li>Mengirimkan notifikasi aktivitas</li><li>Pemeliharaan keamanan dan audit log</li></ul><p class="mt-2 text-amber-700 bg-amber-50 rounded-lg px-3 py-2">Data tidak digunakan untuk iklan atau dijual kepada pihak ketiga.</p>',
                            ],
                            [
                                'icon'    => 'fa-lock',
                                'title'   => '4. Keamanan & Perlindungan Data',
                                'badge'   => 'Keamanan',
                                'color'   => 'bg-rose-50 text-rose-600',
                                'badge_c' => 'bg-rose-50 text-rose-700',
                                'content' => '<ul class="list-disc pl-4 space-y-1"><li>Enkripsi data saat transmisi (TLS/HTTPS)</li><li>Kontrol akses berbasis peran (RBAC)</li><li>Pemantauan dan pencatatan log akses berkala</li><li>Akses data hanya untuk personel berwenang</li></ul>',
                            ],
                            [
                                'icon'    => 'fa-user-shield',
                                'title'   => '5. Hak-Hak Pengguna',
                                'badge'   => 'Hak',
                                'color'   => 'bg-blue-50 text-blue-600',
                                'badge_c' => 'bg-blue-50 text-blue-700',
                                'content' => '<p>Sesuai UU PDP Pasal 5–10, Pengguna berhak:</p><ul class="list-disc pl-4 mt-1 space-y-1"><li><strong>Akses</strong> — meminta info data yang dikumpulkan</li><li><strong>Koreksi</strong> — memperbarui data tidak akurat</li><li><strong>Penghapusan</strong> — meminta hapus data</li><li><strong>Portabilitas</strong> — meminta salinan data</li></ul><p class="mt-2 text-blue-700 bg-blue-50 rounded-lg px-3 py-2">Hubungi: <strong>ATUR2026@gmail.com</strong></p>',
                            ],
                            [
                                'icon'    => 'fa-clock-rotate-left',
                                'title'   => '6. Retensi Data & Penghapusan',
                                'badge'   => 'Retensi',
                                'color'   => 'bg-violet-50 text-violet-600',
                                'badge_c' => 'bg-violet-50 text-violet-700',
                                'content' => '<ul class="list-disc pl-4 space-y-1"><li>Data aktivitas disimpan maksimal <strong>90 hari</strong> setelah akun nonaktif</li><li>Log sistem disimpan maksimal <strong>1 tahun</strong> sesuai regulasi</li><li>Data proyek tetap milik organisasi/workspace terkait</li></ul>',
                            ],
                            [
                                'icon'    => 'fa-pen-to-square',
                                'title'   => '7. Perubahan Kebijakan',
                                'badge'   => 'Revisi',
                                'color'   => 'bg-teal-50 text-teal-600',
                                'badge_c' => 'bg-teal-50 text-teal-700',
                                'content' => '<p>Perubahan signifikan akan diberitahukan melalui notifikasi dalam Sistem paling lambat <strong>14 hari</strong> sebelum berlaku. Versi terbaru selalu tersedia di halaman About ini.</p>',
                            ],
                        ];
                    @endphp

                    @foreach ($policies as $i => $p)
                        <div x-data="{ open: false }" class="border border-slate-200 rounded-xl overflow-hidden">
                            <button @click="open = !open"
                                class="w-full flex items-center gap-3 px-4 py-3 text-left hover:bg-slate-50 transition-colors">
                                <div class="w-7 h-7 rounded-lg {{ $p['color'] }} flex items-center justify-center flex-shrink-0">
                                    <i class="fa-solid {{ $p['icon'] }} text-xs"></i>
                                </div>
                                <span class="flex-1 text-sm font-medium text-slate-800">{{ $p['title'] }}</span>
                                <span class="text-xs font-medium px-2 py-0.5 rounded-full {{ $p['badge_c'] }}">{{ $p['badge'] }}</span>
                                <i class="fa-solid fa-chevron-down text-xs text-slate-400 transition-transform duration-200"
                                    :class="open ? 'rotate-180' : ''"></i>
                            </button>
                            <div x-show="open" x-transition class="px-4 pb-4 pt-1 text-xs text-slate-500 leading-relaxed border-t border-slate-100 pl-14 space-y-1">
                                {!! $p['content'] !!}
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        {{-- Copyright --}}
        <p class="text-center text-xs text-slate-400 pb-4">
            © {{ date('Y') }} ATUR PT REKAINDO Project Management. All rights reserved.
        </p>

    </div>
@endsection
