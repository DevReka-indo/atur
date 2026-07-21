@php
    $statusClasses = match ($task->status) {
        'to_do' => 'bg-amber-100 text-amber-700',
        'in_progress' => 'bg-blue-100 text-blue-700',
        'review' => 'bg-purple-100 text-purple-700',
        'completed' => 'bg-emerald-100 text-emerald-700',
        'stopped' => 'bg-red-100 text-red-700',
        'cancelled' => 'bg-zinc-200 text-zinc-700',
        default => 'bg-slate-100 text-slate-700',
    };
@endphp

<span
    class="inline-flex w-full items-center justify-between gap-2 rounded-md px-3 py-1 text-xs font-medium
        {{ $statusClasses }}"
    title="Status mengikuti status subtask"
>
    <span>
        {{ str($task->status)->replace('_', ' ')->title() }}
    </span>

    <i class="fa-solid fa-lock text-[10px] opacity-60"></i>
</span>
