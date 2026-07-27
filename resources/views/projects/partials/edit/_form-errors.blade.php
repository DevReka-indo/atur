@if ($errors->any())
    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">
        <div class="flex items-start gap-3">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-red-100 text-red-600">
                <i class="fa-solid fa-circle-exclamation"></i>
            </div>
            <div>
                <p class="text-sm font-semibold text-red-800">Beberapa data belum valid</p>
                <ul class="mt-1 list-inside list-disc space-y-1 text-sm text-red-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endif
