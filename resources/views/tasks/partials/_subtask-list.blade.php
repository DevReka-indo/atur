@if ($taskHasSubtasks)
    <section class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm" aria-labelledby="subtask-list-title">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 id="subtask-list-title" class="text-base font-semibold text-gray-900">Subtask</h2>
                <p class="mt-1 text-sm text-gray-500">Struktur ditampilkan maksimal sampai tiga level.</p>
            </div>
            @if ($canAddSubtask)
                <a href="{{ route('tasks.create', ['parent' => $task->token]) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700">
                    <i class="fa-solid fa-plus"></i>
                    Tambah Subtask
                </a>
            @endif
        </div>

        <div class="mt-4 grid gap-3">
            @foreach ($task->subtasks as $subtask)
                @include('tasks.partials._subtask-item', [
                    'subtask' => $subtask,
                    'canContribute' => $canContribute,
                ])
            @endforeach
        </div>
    </section>
@endif
