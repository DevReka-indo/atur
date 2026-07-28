@if ($workspace->projects->isEmpty())
    <div class="text-center py-16 bg-white rounded-2xl shadow-sm border border-gray-200">
        <div
            class="inline-flex items-center justify-center w-16 h-16 rounded-full
                        bg-gradient-to-br from-indigo-100 to-violet-100 text-indigo-600 mb-4">
            <i class="fa-regular fa-folder-open text-2xl"></i>
        </div>
        <h3 class="text-lg font-semibold text-gray-900">Belum ada project</h3>
        <p class="mt-1 text-gray-500">Mulai dengan membuat project pertama di workspace ini.</p>
    </div>
@else
    <div class="bg-white border border-gray-200 shadow-sm mt-4 rounded-xl">
        <div class="overflow-x-auto">
            <table class="min-w-full border-separate border-spacing-0 rounded-xl overflow-hidden">
                <thead>
                    <tr class="bg-[#ADE8F4]">
                        <th
                            class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <div class="flex items-center gap-2">
                                <div class="w-6 h-6 flex-shrink-0"></div>
                                Project
                            </div>
                        </th>
                        <th
                            class="px-5 py-4 text-left text-xs font-semibold uppercase tracking-wider text-gray-600">
                            Creator </th>
                        <th
                            class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Status</th>
                        <th
                            class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider min-w-[200px]">
                            Progress</th>
                        <th
                            class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Tasks</th>
                        <th
                            class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">

                    @foreach ($workspace->projects as $project)
                        @php
                            $totalWeight = $project->tasks->sum('weight');
                            $earnedValue = $project->tasks->sum(
                                fn($task) => $task->weight * ($task->statusWeight->weight_value ?? 0),
                            );
                            $progress = $totalWeight > 0 ? ($earnedValue / $totalWeight) * 100 : 0;

                            // Hitung di sini agar tersedia untuk kondisi <tr> di bawah
                            $wsProgressVal = min(round($progress), 100);
                            $wsHue = ($wsProgressVal / 100) * 120;
                            $wsColorStart = "hsl($wsHue, 65%, 75%)";
                            $wsColorEnd = 'hsl(' . ($wsHue + 10) . ', 70%, 70%)';
                            $wsTextColor = $wsProgressVal >= 100 ? 'text-emerald-500' : 'text-gray-600';

                            $statusConfig = [
                                'planning' => [
                                    'class' => 'bg-slate-100 text-slate-700 border border-slate-200',
                                    'hover' => 'hover:bg-slate-200',
                                ],
                                'in_progress' => [
                                    'class' => 'bg-blue-100 text-blue-700 border border-blue-200',
                                    'hover' => 'hover:bg-blue-200',
                                ],
                                'active' => [
                                    'class' => 'bg-green-100 text-green-700 border border-green-200',
                                    'hover' => 'hover:bg-green-200',
                                ],
                                'review' => [
                                    'class' => 'bg-amber-100 text-amber-700 border border-amber-200',
                                    'hover' => 'hover:bg-amber-200',
                                ],
                                'completed' => [
                                    'class' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
                                    'hover' => 'hover:bg-emerald-200',
                                ],
                                'on_hold' => [
                                    'class' => 'bg-yellow-100 text-yellow-700 border border-gray-200',
                                    'hover' => 'hover:bg-gray-200',
                                ],
                                'urgent' => [
                                    'class' => 'bg-orange-200 text-orange-800 border border-orange-400',
                                    'hover' => 'hover:bg-orange-300',
                                ],
                            ];

                            $config = $statusConfig[$project->status] ?? $statusConfig['planning'];
                        @endphp

                        {{-- Row untuk semua project --}}
                        <tr
                            class="hover:bg-gray-50 border-b border-gray-100 transition-colors duration-150
                                {{ $project->status === 'urgent' && $wsProgressVal < 100 ? 'urgent-row' : '' }}">

                            {{-- Project name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    {{-- Warning Icon + Badge untuk Urgent --}}
                                    @if ($project->status === 'urgent' && $wsProgressVal < 100)
                                        <div class="w-6 h-6 flex-shrink-0">
                                            <div
                                                class="w-6 h-6 flex items-center justify-center rounded-lg flex-shrink-0 bg-gradient-to-br from-red-600 to-red-500 shadow-[0_2px_4px_rgba(220,38,38,0.3)]">
                                                <i
                                                    class="fa-solid fa-triangle-exclamation text-white text-xs animate-pulse"></i>
                                            </div>
                                        </div>
                                    @else
                                        {{-- Placeholder agar alignment tetap rata --}}
                                        <div class="w-6 h-6 flex-shrink-0"></div>
                                    @endif

                                    {{-- Project Initial Badge --}}
                                    <div
                                        class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100
                                    flex items-center justify-center text-indigo-700 font-semibold text-sm">
                                        {{ strtoupper(substr($project->name, 0, 1)) }}
                                    </div>

                                    {{-- Project Name --}}
                                    <span
                                        class="font-medium text-gray-900 whitespace-nowrap">{{ $project->name }}</span>
                                </div>
                            </td>

                            {{-- Creator --}}
                            <td class="px-5 py-4">
                                @if ($project->creator)
                                    <div class="flex items-center gap-2">
                                        @if ($project->creator->profile_photo)
                                            <img src="{{ asset('storage/' . $project->creator->profile_photo) }}"
                                                class="w-7 h-7 rounded-full object-cover border-2 border-white shadow-sm flex-shrink-0">
                                        @else
                                            <div
                                                class="w-7 h-7 rounded-full bg-indigo-100 flex items-center justify-center flex-shrink-0">
                                                <span class="text-xs font-bold text-indigo-600">
                                                    {{ strtoupper(substr($project->creator->name, 0, 1)) }}
                                                </span>
                                            </div>
                                        @endif
                                        <span
                                            class="text-sm text-gray-700">{{ $project->creator->name }}</span>
                                    </div>
                                @else
                                    <span class="text-sm text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Status dropdown --}}
                            <td class="px-6 py-4">
                                @php
                                    $statusOptions = [
                                        'planning' => [
                                            'label' => 'Planning',
                                            'class' => 'bg-gray-100 text-gray-700',
                                        ],
                                        'active' => [
                                            'label' => 'Active',
                                            'class' => 'bg-green-100 text-green-700',
                                        ],
                                        'completed' => [
                                            'label' => 'Completed',
                                            'class' => 'bg-emerald-100 text-emerald-700',
                                        ],
                                        'on_hold' => [
                                            'label' => 'On hold',
                                            'class' => 'bg-yellow-100 text-yellow-700',
                                        ],
                                        'cancelled' => [
                                            'label' => 'Cancelled',
                                            'class' => 'bg-red-100 text-red-700',
                                        ],
                                        'urgent' => [
                                            'label' => 'Urgent',
                                            'class' => 'bg-orange-200 text-orange-800',
                                        ],
                                    ];

                                    $currentStatus = $project->status;
                                    $currentOption =
                                        $statusOptions[$currentStatus] ?? $statusOptions['planning'];
                                @endphp
                                @php
                                    $canChangeStatus = $isOwner || $currentRole === 'admin';
                                @endphp

                                @if ($canChangeStatus)
                                    {{-- Dropdown untuk Owner/Admin --}}
                                    <button type="button"
                                        onclick="toggleProjectStatusDropdown({{ $project->id }}, this)"
                                        data-project-id="{{ $project->id }}"
                                        data-update-url="{{ route('projects.updateStatus', $project->token) }}"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
        {{ $currentOption['class'] }} hover:opacity-80 transition-all cursor-pointer
        w-32 justify-between flex-shrink-0 border-0">
                                        <span class="flex items-center gap-1.5 truncate flex-1">
                                            {{ $currentOption['label'] }}
                                        </span>
                                        <i
                                            class="fa-solid fa-chevron-down text-[8px] opacity-60 flex-shrink-0"></i>
                                    </button>

                                    {{-- Dropdown Menu --}}
                                    <div id="status-dropdown-{{ $project->id }}"
                                        class="hidden absolute z-50 mt-1 w-40 bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden py-1">
                                        @foreach ($statusOptions as $value => $option)
                                            <form method="POST"
                                                action="{{ route('projects.updateStatus', $project->token) }}"
                                                class="status-form-{{ $project->id }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status"
                                                    value="{{ $value }}">
                                                <button type="submit"
                                                    onclick="updateProjectStatus(event, {{ $project->id }})"
                                                    class="w-full text-left px-3 py-2 text-xs transition-colors flex items-center gap-2
                    {{ $value === $currentStatus ? 'bg-gray-100 font-semibold' : 'hover:bg-gray-50' }} border-0">
                                                    @if ($value === $currentStatus)
                                                        <i
                                                            class="fa-solid fa-check text-green-500 text-[10px]"></i>
                                                    @else
                                                        <span class="w-3"></span>
                                                    @endif
                                                    <span>{{ $option['label'] }}</span>
                                                </button>
                                            </form>
                                        @endforeach
                                    </div>
                                @else
                                    {{-- Badge static untuk non-owner/admin --}}
                                    <span
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold
    {{ $currentOption['class'] }} transition-colors cursor-default
    w-32 justify-center flex-shrink-0 border-0">
                                        {{ str($project->status)->replace('_', ' ')->title() }}
                                    </span>
                                @endif
                            </td>

                            {{-- Progress --}}
                            <td class="px-6 py-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <div class="flex-1 h-2 bg-gray-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-500"
                                            style="width: {{ $wsProgressVal }}%;
                        background: linear-gradient(90deg, {{ $wsColorStart }}, {{ $wsColorEnd }});
                        box-shadow: inset 0 1px 2px rgba(0,0,0,0.04);">
                                        </div>
                                    </div>
                                    <span class="text-sm font-medium {{ $wsTextColor }} w-12 text-right">
                                        {{ number_format($wsProgressVal, 0) }}%
                                    </span>
                                </div>
                            </td>

                            {{-- Tasks count --}}
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <span class="font-medium text-gray-900">{{ $project->tasks_count }}</span>
                                tasks
                            </td>

                            {{-- Actions --}}
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('projects.show', $project->token) }}"
                                        class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-blue-500 hover:bg-blue-50 transition-colors">
                                        <i class="fa-regular fa-eye"></i>
                                    </a>
                                    @if ($isOwner)
                                        <a href="{{ route('projects.edit', $project->token) }}"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-amber-500 hover:bg-amber-50 transition-colors">
                                            <i class="fa-solid fa-pen"></i>
                                        </a>
                                        <form action="{{ route('projects.destroy', $project->token) }}"
                                            method="POST" onsubmit="return confirm('Hapus project ini?')">
                                            @csrf @method('DELETE')
                                            <button type="submit"
                                                class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-red-500 hover:bg-red-50 transition-colors cursor-pointer">
                                                <i class="fa-regular fa-trash-can"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>

                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif
