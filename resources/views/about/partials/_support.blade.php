<aside class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-7" aria-labelledby="support-title">
    <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-50 text-emerald-700">
        <i class="fa-solid fa-headset" aria-hidden="true"></i>
    </span>
    <p class="mt-5 text-xs font-semibold uppercase tracking-[0.16em] text-emerald-700">Bantuan</p>
    <h2 id="support-title" class="mt-2 text-xl font-bold tracking-tight text-slate-950">Dukungan Aplikasi</h2>
    <p class="mt-2 text-sm leading-6 text-slate-600">
        Hubungi tim dukungan untuk bantuan akses, penggunaan fitur, atau pelaporan kendala.
    </p>
    <a
        href="mailto:{{ config('atur.support_email') }}"
        class="mt-5 inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-emerald-300 hover:text-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2"
    >
        <i class="fa-solid fa-envelope" aria-hidden="true"></i>
        {{ config('atur.support_email') }}
    </a>
</aside>
