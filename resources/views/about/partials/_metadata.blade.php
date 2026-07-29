<dl class="mt-6 grid grid-cols-1 gap-x-6 gap-y-5 border-t border-slate-200 pt-6 sm:grid-cols-2">
    <div>
        <dt class="text-xs font-medium text-slate-500">Dikembangkan oleh</dt>
        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ config('atur.developer') }}</dd>
    </div>
    <div>
        <dt class="text-xs font-medium text-slate-500">Tahun Rilis</dt>
        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ config('atur.release_year') }}</dd>
    </div>
    <div>
        <dt class="text-xs font-medium text-slate-500">Dukungan</dt>
        <dd class="mt-1">
            <a
                href="mailto:{{ config('atur.support_email') }}"
                class="text-sm font-semibold text-blue-700 hover:text-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                {{ config('atur.support_email') }}
            </a>
        </dd>
    </div>
    <div>
        <dt class="text-xs font-medium text-slate-500">Lisensi</dt>
        <dd class="mt-1 text-sm font-semibold text-slate-900">{{ config('atur.license') }}</dd>
    </div>
</dl>
