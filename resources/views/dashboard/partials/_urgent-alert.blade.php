@if (($projectStats['urgent'] ?? 0) > 0)
    <div id="urgent-projects-alert" class="mb-6 px-4 sm:px-6">
        <div
            class="rounded-xl px-5 py-4 shadow-md shadow-red-200/60 cursor-pointer transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg"
            style="background: linear-gradient(to right, #fee2e2, #fce7f3);"
            onclick="window.location.href='{{ route('projects.index', ['status' => 'urgent']) }}'">
            <div class="flex items-center justify-between gap-4">
                <div class="flex items-center gap-3 flex-1 min-w-0">
                    <div
                        class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0 shadow-md shadow-red-300/60"
                        style="background: linear-gradient(135deg, #dc2626, #ef4444);">
                        <i class="fa-solid fa-triangle-exclamation text-white text-2xl"></i>
                    </div>

                    <div class="flex-1 min-w-0">
                        <p class="text-[15px] font-bold text-red-900 flex items-center gap-2">
                            <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                            Urgent Projects Require Attention
                        </p>

                        <p class="text-sm text-red-600 mt-1 font-medium">
                            You have
                            <span class="font-bold text-red-900 text-base">
                                {{ $projectStats['urgent'] }}
                            </span>
                            urgent project{{ $projectStats['urgent'] > 1 ? 's' : '' }}
                        </p>
                    </div>
                </div>

                <div class="flex items-center gap-3 flex-shrink-0">
                    <span
                        class="hidden sm:inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-600 text-white text-xs font-bold rounded-lg">
                        View Now
                        <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </span>

                    <button
                        type="button"
                        onclick="event.stopPropagation(); closeUrgentAlert()"
                        class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-100 text-red-600 border-0 cursor-pointer hover:bg-red-200 transition-colors">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
@endif
