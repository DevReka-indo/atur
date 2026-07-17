@if ($overloadedMembers->count() > 0)
    <div id="overload-alert" class="mb-6 px-4 sm:px-6">
        <div
            class="rounded-xl px-5 py-4 shadow-md shadow-orange-200/60 cursor-pointer transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
            style="background: linear-gradient(to right, #fff7ed, #fef3c7);"
            onclick="window.location.href='{{ route('overload.index') }}'">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md shadow-orange-300/60"
                        style="background: linear-gradient(135deg, #ea580c, #f97316);">
                        <i class="fa-solid fa-user-clock text-white text-xl"></i>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-[15px] font-bold text-orange-900">
                            <i class="fa-solid fa-triangle-exclamation text-orange-600"></i>
                            {{ $overloadedMembers->count() }} Member Overload Terdeteksi
                        </p>

                        <p class="text-sm text-orange-700 mt-1">
                            @foreach ($overloadedMembers->take(3) as $om)
                                <span class="font-semibold">{{ $om['name'] }}</span>
                                ({{ $om['project'] }} · {{ $om['task_count'] }} tasks)
                                {{ !$loop->last ? ', ' : '' }}
                            @endforeach

                            @if ($overloadedMembers->count() > 3)
                                <span class="font-semibold">
                                    +{{ $overloadedMembers->count() - 3 }} lainnya
                                </span>
                            @endif
                        </p>
                    </div>
                </div>

                <button
                    type="button"
                    onclick="event.stopPropagation(); closeOverloadAlert()"
                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-orange-50 text-orange-600 border-0 cursor-pointer hover:bg-orange-100 transition-colors flex-shrink-0">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>
    </div>
@endif
