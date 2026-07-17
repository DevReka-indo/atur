<div
    class="widget-card bg-white rounded-xl shadow-md border border-gray-200/60 overflow-hidden flex flex-col cursor-grab active:cursor-grabbing"
    style="height: 420px;"
    data-id="widget-projects">
    <div
        class="widget-header px-5 py-4 border-b border-gray-100 flex items-center justify-between bg-gradient-to-r from-gray-50/50 to-transparent flex-shrink-0">
        <div class="flex items-center gap-3">
            <div
                class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-100 to-[#5DDA52] flex items-center justify-center text-[#088B01]">
                <i class="fa-regular fa-folder-open"></i>
            </div>

            <div>
                <h2 class="text-sm font-bold text-gray-900">
                    Active Projects
                </h2>

                <p class="text-xs text-gray-500">
                    Ongoing project
                </p>
            </div>
        </div>

        <a href="{{ route('projects.index') }}"
            class="no-drag text-xs font-medium text-indigo-600 hover:text-indigo-700">
            See all
            <i class="fa-solid fa-arrow-right text-[10px] ml-0.5"></i>
        </a>
    </div>

    @if ($activeProjects->isEmpty())
        <div class="flex-1 flex items-center justify-center">
            <div class="text-center">
                <div
                    class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 flex items-center justify-center mx-auto mb-3">
                    <i class="fa-regular fa-folder text-2xl text-indigo-600"></i>
                </div>

                <p class="text-gray-700 font-semibold text-sm">
                    No active projects
                </p>

                <a href="{{ route('projects.create') }}"
                    class="mt-3 inline-flex items-center gap-2 px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                    <i class="fa-solid fa-plus"></i>
                    New Project
                </a>
            </div>
        </div>
    @else
        <div class="no-drag divide-y divide-gray-100 flex-1 overflow-y-auto">
            @foreach ($activeProjects as $project)
                @php
                    $projectProgress = min(round($project->calculateProgress()), 100);

                    $hue = ($projectProgress / 100) * 120;
                    $colorStart = "hsl($hue, 85%, 55%)";
                    $colorEnd = 'hsl(' . ($hue + 15) . ', 80%, 50%)';

                    $textColor = $projectProgress >= 100
                        ? 'text-emerald-600'
                        : 'text-gray-700';
                @endphp

                <div
                    class="px-5 py-3 hover:bg-gray-50 transition-all group cursor-pointer"
                    onclick="window.location.href='{{ route('projects.show', $project->token) }}'">
                    <div class="flex items-center justify-between gap-3 mb-2">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            <div
                                class="w-8 h-8 rounded-lg bg-gradient-to-br from-indigo-100 to-[#A3E1EE] flex items-center justify-center text-[#0096c7] font-semibold text-xs flex-shrink-0">
                                {{ strtoupper(substr($project->name, 0, 1)) }}
                            </div>

                            <div class="min-w-0">
                                <p
                                    class="text-sm font-medium text-gray-900 group-hover:text-indigo-600 transition-colors truncate">
                                    {{ $project->name }}
                                </p>

                                <p class="text-xs text-gray-400 truncate">
                                    {{ $project->workspace?->name ?? 'No Workspace' }}
                                </p>
                            </div>
                        </div>

                        <span class="text-xs font-bold {{ $textColor }} flex-shrink-0">
                            {{ $projectProgress }}%
                        </span>
                    </div>

                    <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div
                            class="h-1.5 rounded-full transition-all duration-500"
                            style="width: {{ $projectProgress }}%; background: linear-gradient(90deg, {{ $colorStart }}, {{ $colorEnd }});">
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
