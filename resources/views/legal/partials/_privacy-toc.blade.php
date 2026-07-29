<nav aria-label="Daftar isi Kebijakan Privasi">
    <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-500">Daftar Isi</p>
    <ol class="mt-3 flex flex-col gap-1">
        @foreach ([
            'privacy-general' => 'Ketentuan Umum & Ruang Lingkup',
            'privacy-data' => 'Data Pribadi yang Dikumpulkan',
            'privacy-purpose' => 'Tujuan Penggunaan Data',
            'privacy-security' => 'Keamanan & Perlindungan Data',
            'privacy-rights' => 'Hak-Hak Pengguna',
            'privacy-retention' => 'Retensi Data & Penghapusan',
            'privacy-changes' => 'Perubahan Kebijakan',
        ] as $sectionId => $sectionTitle)
            <li>
                <a
                    href="#{{ $sectionId }}"
                    class="block rounded-lg px-3 py-2 text-sm leading-5 text-slate-600 transition hover:bg-blue-50 hover:text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    data-privacy-toc-link
                >
                    {{ $loop->iteration }}. {{ $sectionTitle }}
                </a>
            </li>
        @endforeach
    </ol>
</nav>
