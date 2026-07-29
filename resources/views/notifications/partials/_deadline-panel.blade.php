<aside class="lg:col-span-1" aria-labelledby="deadline-panel-heading">
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm lg:sticky lg:top-20">
        <div class="flex items-center justify-between gap-3">
            <div>
                <h2 id="deadline-panel-heading" class="font-semibold text-slate-900">Deadline Approaching</h2>
                <p class="mt-1 text-xs leading-5 text-slate-500">Tasks due within the next three days.</p>
            </div>
            <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold tabular-nums text-slate-600">
                {{ $deadlineItems->count() }}
            </span>
        </div>

        <div class="mt-5 space-y-3">
            @forelse ($deadlineItems as $item)
                @php
                    $task = $item['task'];
                    $deadline = $item['presentation'];
                @endphp
                <a
                    href="{{ route('tasks.show', $task) }}"
                    class="block rounded-xl border p-4 transition hover:-translate-y-0.5 hover:shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 {{ $deadline['card_classes'] }}"
                    aria-label="Open task {{ $task->name }}, {{ $deadline['label'] }}"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="line-clamp-2 text-sm font-semibold leading-5 text-slate-900">{{ $task->name }}</p>
                        <span
                            class="inline-flex flex-none items-center gap-1 rounded-full px-2 py-1 text-[11px] font-semibold ring-1 ring-inset {{ $deadline['badge_classes'] }}"
                        >
                            <i class="fa-solid {{ $deadline['icon'] }}" aria-hidden="true"></i>
                            {{ $deadline['label'] }}
                        </span>
                    </div>

                    @if ($task->project)
                        <p class="mt-2 flex items-center gap-1.5 text-xs text-slate-500">
                            <i class="fa-solid fa-folder-open" aria-hidden="true"></i>
                            {{ $task->project->name }}
                        </p>
                    @endif

                    <time class="mt-2 block text-xs font-medium text-slate-600" datetime="{{ $task->due_date->toDateString() }}">
                        {{ $deadline['due_date'] }}
                    </time>
                </a>
            @empty
                <div class="rounded-xl border border-emerald-200 bg-emerald-50/60 p-5 text-center">
                    <i class="fa-solid fa-circle-check text-emerald-600" aria-hidden="true"></i>
                    <p class="mt-2 text-sm font-semibold text-emerald-800">No approaching deadlines</p>
                    <p class="mt-1 text-xs leading-5 text-emerald-700">Your next three days are clear.</p>
                </div>
            @endforelse
        </div>
    </div>
</aside>
