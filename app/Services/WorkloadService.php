<?php

namespace App\Services;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkloadService
{
    public const DISCLAIMER = 'Skor Beban Tugas merupakan indikator distribusi task aktif pada periode terpilih. Skor ini bukan estimasi jam kerja, Story Point, pengukuran produktivitas, atau kapasitas kerja presisi.';

    /**
     * @return array{
     *     members: LengthAwarePaginator<int, array<string, mixed>>,
     *     summary: array<string, int>,
     *     filters: array<string, mixed>,
     *     period: array<string, string>,
     *     workspaces: Collection<int, Workspace>,
     *     projects: Collection<int, Project>,
     *     levels: array<string, array<string, string>>,
     *     thresholds: array<string, float>,
     *     is_super_admin: bool
     * }
     */
    public function index(User $actor, array $filters): array
    {
        $normalizedFilters = $this->normalizeFilters($actor, $filters);
        $authorizedProjectIds = $this->authorizedProjectIds($actor, $normalizedFilters['scope']);
        $normalizedFilters = $this->normalizeScopedFilters(
            $actor,
            $authorizedProjectIds,
            $normalizedFilters,
        );
        $period = $this->resolvePeriod($normalizedFilters);
        $projectIds = $this->filteredProjectIds($authorizedProjectIds, $normalizedFilters);
        unset($normalizedFilters['has_invalid_scope_filter']);
        $memberQuery = $this->memberQuery($projectIds, $period, $normalizedFilters);
        $summary = $this->summary(clone $memberQuery);

        $members = $memberQuery
            ->orderByDesc('workload_score')
            ->orderBy('users.name')
            ->paginate((int) config('atur.workload.per_page', 15))
            ->appends(array_filter(
                $normalizedFilters,
                fn (mixed $value): bool => $value !== null && $value !== '',
            ))
            ->through(fn (User $member): array => $this->presentMember($member));

        return [
            'members' => $members,
            'summary' => $summary,
            'filters' => $normalizedFilters,
            'period' => $period,
            'workspaces' => $this->workspaceOptions(
                $actor,
                $normalizedFilters['scope'],
                $authorizedProjectIds,
            ),
            'projects' => $this->projectOptions($authorizedProjectIds, $normalizedFilters),
            'levels' => $this->levels(),
            'thresholds' => $this->thresholds(),
            'is_super_admin' => $actor->isSuperAdmin(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function detail(User $actor, User $member, array $filters): array
    {
        $normalizedFilters = $this->normalizeFilters($actor, $filters);
        $authorizedProjectIds = $this->authorizedProjectIds($actor, $normalizedFilters['scope']);
        $normalizedFilters = $this->normalizeScopedFilters(
            $actor,
            $authorizedProjectIds,
            $normalizedFilters,
        );
        $period = $this->resolvePeriod($normalizedFilters);
        $projectIds = $this->filteredProjectIds($authorizedProjectIds, $normalizedFilters);
        unset($normalizedFilters['has_invalid_scope_filter']);
        $memberRecord = $this->memberQuery($projectIds, $period, $normalizedFilters)
            ->whereKey($member->id)
            ->firstOrFail();
        $tasksTable = (new Task)->getTable();
        $projectsTable = (new Project)->getTable();

        $taskRows = DB::query()
            ->fromSub($this->taskContributionQuery($projectIds, $period), 'workload_tasks')
            ->join($tasksTable, "{$tasksTable}.id", '=', 'workload_tasks.task_id')
            ->join($projectsTable, "{$projectsTable}.id", '=', 'workload_tasks.project_id')
            ->where('workload_tasks.user_id', $member->id)
            ->select([
                "{$tasksTable}.id",
                "{$tasksTable}.name",
                "{$tasksTable}.token",
                "{$tasksTable}.status",
                "{$tasksTable}.start_date",
                "{$tasksTable}.due_date",
                "{$projectsTable}.id as project_id",
                "{$projectsTable}.name as project_name",
                "{$projectsTable}.token as project_token",
                'workload_tasks.active_assignee_count',
                'workload_tasks.contribution',
                'workload_tasks.is_scheduled',
                'workload_tasks.is_overdue',
            ])
            ->orderBy("{$projectsTable}.name")
            ->orderBy("{$tasksTable}.due_date")
            ->orderBy("{$tasksTable}.name")
            ->get()
            ->map(fn (object $task): array => [
                'id' => (int) $task->id,
                'name' => $task->name,
                'status' => $task->status,
                'start_date' => $task->start_date,
                'due_date' => $task->due_date,
                'project_id' => (int) $task->project_id,
                'project_name' => $task->project_name,
                'active_assignee_count' => (int) $task->active_assignee_count,
                'contribution' => round((float) $task->contribution, 2),
                'is_scheduled' => (bool) $task->is_scheduled,
                'is_overdue' => (bool) $task->is_overdue,
                'task_url' => route('tasks.show', $task->token),
                'project_url' => route('projects.show', $task->project_token),
            ]);

        $projects = $taskRows
            ->groupBy('project_id')
            ->map(function (Collection $projectTasks): array {
                $firstTask = $projectTasks->first();
                $scheduledTasks = $projectTasks->where('is_scheduled', true);

                return [
                    'id' => $firstTask['project_id'],
                    'name' => $firstTask['project_name'],
                    'url' => $firstTask['project_url'],
                    'score' => round($scheduledTasks->sum('contribution'), 2),
                    'task_count' => $scheduledTasks->count(),
                    'overdue_count' => $scheduledTasks->where('is_overdue', true)->count(),
                    'unscheduled_count' => $projectTasks->where('is_scheduled', false)->count(),
                ];
            })
            ->values();

        return [
            'member' => $this->presentMember($memberRecord),
            'projects' => $projects,
            'contributing_tasks' => $taskRows->where('is_scheduled', true)->values(),
            'unscheduled_tasks' => $taskRows->where('is_scheduled', false)->values(),
            'period' => $period,
            'disclaimer' => self::DISCLAIMER,
        ];
    }

    public function canView(User $user): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return Workspace::query()
            ->where('created_by', $user->id)
            ->orWhereHas('members', fn (EloquentBuilder $query): EloquentBuilder => $query
                ->where('users.id', $user->id)
                ->where('workspace_members.role', Workspace::ROLE_ADMIN))
            ->exists()
            || Project::query()
                ->whereHas('members', fn (EloquentBuilder $query): EloquentBuilder => $query
                    ->where('users.id', $user->id)
                    ->whereIn('project_members.role', [Project::ROLE_MANAGER, Project::ROLE_MEMBER]))
                ->exists();
    }

    public function resolveScope(User $actor, ?string $requestedScope): string
    {
        if (! $actor->isSuperAdmin()) {
            return 'managed';
        }

        return $requestedScope === 'all' ? 'all' : 'managed';
    }

    public function isGlobalScope(User $actor, string $scope): bool
    {
        return $actor->isSuperAdmin() && $scope === 'all';
    }

    /**
     * @return Collection<int, int>
     */
    public function accessibleWorkspaceIdsForManagedScope(User $actor): Collection
    {
        return Workspace::query()
            ->where('created_by', $actor->id)
            ->orWhereHas('members', fn (EloquentBuilder $query): EloquentBuilder => $query
                ->where('users.id', $actor->id)
                ->where('workspace_members.role', Workspace::ROLE_ADMIN))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
    }

    /**
     * @return Collection<int, int>
     */
    public function accessibleProjectIdsForManagedScope(User $actor): Collection
    {
        $managedWorkspaceIds = $this->accessibleWorkspaceIdsForManagedScope($actor);

        return Project::query()
            ->whereIn('status', config('atur.workload.active_project_statuses', ['active', 'urgent']))
            ->where(function (EloquentBuilder $query) use ($actor, $managedWorkspaceIds): void {
                $query->whereIn('workspace_id', $managedWorkspaceIds)
                    ->orWhereHas('members', fn (EloquentBuilder $memberQuery): EloquentBuilder => $memberQuery
                        ->where('users.id', $actor->id)
                        ->whereIn('project_members.role', [Project::ROLE_MANAGER, Project::ROLE_MEMBER]));
            })
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function levels(): array
    {
        return [
            'normal' => [
                'label' => 'Normal',
                'badge' => 'bg-emerald-100 text-emerald-700 ring-emerald-600/20',
                'dot' => 'bg-emerald-500',
            ],
            'attention' => [
                'label' => 'Perlu Perhatian',
                'badge' => 'bg-amber-100 text-amber-800 ring-amber-600/20',
                'dot' => 'bg-amber-500',
            ],
            'high_risk' => [
                'label' => 'Risiko Tinggi',
                'badge' => 'bg-orange-100 text-orange-800 ring-orange-600/20',
                'dot' => 'bg-orange-500',
            ],
            'critical' => [
                'label' => 'Kritis',
                'badge' => 'bg-red-100 text-red-700 ring-red-600/20',
                'dot' => 'bg-red-500',
            ],
        ];
    }

    /**
     * @return array<string, float>
     */
    public function thresholds(): array
    {
        return [
            'attention' => (float) config('atur.workload.thresholds.attention', 5),
            'high_risk' => (float) config('atur.workload.thresholds.high_risk', 7),
            'critical' => (float) config('atur.workload.thresholds.critical', 9),
        ];
    }

    public function levelForScore(float $score): string
    {
        $thresholds = $this->thresholds();

        return match (true) {
            $score >= $thresholds['critical'] => 'critical',
            $score >= $thresholds['high_risk'] => 'high_risk',
            $score >= $thresholds['attention'] => 'attention',
            default => 'normal',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeFilters(User $actor, array $filters): array
    {
        return [
            'scope' => $this->resolveScope($actor, $filters['scope'] ?? null),
            'period' => $filters['period'] ?? config('atur.workload.default_period', 'next_7_days'),
            'start_date' => $filters['start_date'] ?? null,
            'end_date' => $filters['end_date'] ?? null,
            'workspace' => isset($filters['workspace']) ? (int) $filters['workspace'] : null,
            'project' => isset($filters['project']) ? (int) $filters['project'] : null,
            'level' => $filters['level'] ?? null,
            'search' => trim((string) ($filters['search'] ?? '')),
        ];
    }

    /**
     * @param  Collection<int, int>  $authorizedProjectIds
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function normalizeScopedFilters(
        User $actor,
        Collection $authorizedProjectIds,
        array $filters,
    ): array {
        $workspaceIds = $this->filterableWorkspaceIds($actor, $filters['scope'], $authorizedProjectIds);
        $filters['has_invalid_scope_filter'] = false;

        if ($filters['workspace'] && ! $workspaceIds->containsStrict($filters['workspace'])) {
            $filters['workspace'] = null;
            $filters['has_invalid_scope_filter'] = true;
        }

        if ($filters['project'] && ! $authorizedProjectIds->containsStrict($filters['project'])) {
            $filters['project'] = null;
            $filters['has_invalid_scope_filter'] = true;
        }

        if ($filters['workspace'] && $filters['project']) {
            $projectMatchesWorkspace = Project::query()
                ->whereKey($filters['project'])
                ->where('workspace_id', $filters['workspace'])
                ->exists();

            if (! $projectMatchesWorkspace) {
                $filters['project'] = null;
                $filters['has_invalid_scope_filter'] = true;
            }
        }

        return $filters;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{key: string, label: string, start: string, end: string}
     */
    private function resolvePeriod(array $filters): array
    {
        $today = CarbonImmutable::now(config('app.timezone'))->startOfDay();
        $period = $filters['period'];

        [$label, $start, $end] = match ($period) {
            'this_week' => ['Minggu Ini', $today->startOfWeek(), $today->endOfWeek()],
            'this_month' => ['Bulan Ini', $today->startOfMonth(), $today->endOfMonth()],
            'custom' => [
                'Rentang Kustom',
                CarbonImmutable::createFromFormat('Y-m-d', $filters['start_date'], config('app.timezone'))->startOfDay(),
                CarbonImmutable::createFromFormat('Y-m-d', $filters['end_date'], config('app.timezone'))->startOfDay(),
            ],
            default => ['7 Hari ke Depan', $today, $today->addDays(6)],
        };

        return [
            'key' => $period,
            'label' => $label,
            'start' => $start->toDateString(),
            'end' => $end->toDateString(),
        ];
    }

    /**
     * @return Collection<int, int>
     */
    private function authorizedProjectIds(User $actor, string $scope): Collection
    {
        $operationalStatuses = config('atur.workload.active_project_statuses', ['active', 'urgent']);

        if ($this->isGlobalScope($actor, $scope)) {
            return Project::query()
                ->whereIn('status', $operationalStatuses)
                ->pluck('id')
                ->map(fn ($id): int => (int) $id);
        }

        return $this->accessibleProjectIdsForManagedScope($actor);
    }

    /**
     * @param  Collection<int, int>  $authorizedProjectIds
     * @param  array<string, mixed>  $filters
     * @return Collection<int, int>
     */
    private function filteredProjectIds(Collection $authorizedProjectIds, array $filters): Collection
    {
        if ($filters['has_invalid_scope_filter']) {
            return collect();
        }

        $query = Project::query()->whereIn('id', $authorizedProjectIds);

        if ($filters['workspace']) {
            $query->where('workspace_id', $filters['workspace']);
        }

        if ($filters['project']) {
            $query->whereKey($filters['project']);
        }

        return $query->pluck('id')->map(fn ($id): int => (int) $id);
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @param  array{start: string, end: string}  $period
     * @param  array<string, mixed>  $filters
     */
    private function memberQuery(
        Collection $projectIds,
        array $period,
        array $filters,
    ): EloquentBuilder {
        $aggregateQuery = DB::query()
            ->fromSub($this->taskContributionQuery($projectIds, $period), 'workload_tasks')
            ->select('user_id')
            ->selectRaw('SUM(contribution) as workload_score')
            ->selectRaw('SUM(is_scheduled) as contributing_task_count')
            ->selectRaw('COUNT(DISTINCT CASE WHEN is_scheduled = 1 THEN project_id END) as contributing_project_count')
            ->selectRaw('SUM(is_overdue) as overdue_count')
            ->selectRaw('SUM(CASE WHEN is_scheduled = 0 THEN 1 ELSE 0 END) as unscheduled_count')
            ->groupBy('user_id');

        $query = User::query()
            ->select([
                'users.id',
                'users.name',
                'users.email',
                'users.employee_id',
                'users.profile_photo',
                'users.job_title',
            ])
            ->selectRaw('COALESCE(workload_totals.workload_score, 0) as workload_score')
            ->selectRaw('COALESCE(workload_totals.contributing_task_count, 0) as contributing_task_count')
            ->selectRaw('COALESCE(workload_totals.contributing_project_count, 0) as contributing_project_count')
            ->selectRaw('COALESCE(workload_totals.overdue_count, 0) as overdue_count')
            ->selectRaw('COALESCE(workload_totals.unscheduled_count, 0) as unscheduled_count')
            ->leftJoinSub($aggregateQuery, 'workload_totals', 'workload_totals.user_id', '=', 'users.id')
            ->where('users.is_active', true);

        $query->whereExists(function (Builder $memberQuery) use ($projectIds): void {
            $memberQuery
                ->selectRaw('1')
                ->from('project_members')
                ->whereColumn('project_members.user_id', 'users.id')
                ->whereIn('project_members.project_id', $projectIds);
        });

        if ($filters['search'] !== '') {
            $search = '%'.$filters['search'].'%';
            $query->where(function (EloquentBuilder $searchQuery) use ($search): void {
                $searchQuery
                    ->where('users.name', 'like', $search)
                    ->orWhere('users.email', 'like', $search)
                    ->orWhere('users.employee_id', 'like', $search);
            });
        }

        $this->applyLevelFilter($query, $filters['level']);

        return $query;
    }

    /**
     * @param  Collection<int, int>  $projectIds
     * @param  array{start: string, end: string}  $period
     */
    private function taskContributionQuery(Collection $projectIds, array $period): Builder
    {
        $tasksTable = (new Task)->getTable();
        $assignments = $this->assignmentQuery($projectIds);
        $assignmentCounts = DB::query()
            ->fromSub(clone $assignments, 'active_assignments')
            ->select('task_id')
            ->selectRaw('COUNT(*) as active_assignee_count')
            ->groupBy('task_id');

        return DB::query()
            ->fromSub($assignments, 'workload_assignments')
            ->joinSub($assignmentCounts, 'assignment_counts', 'assignment_counts.task_id', '=', 'workload_assignments.task_id')
            ->join($tasksTable, "{$tasksTable}.id", '=', 'workload_assignments.task_id')
            ->whereIn("{$tasksTable}.project_id", $projectIds)
            ->whereIn("{$tasksTable}.status", config('atur.workload.active_task_statuses', ['to_do', 'in_progress', 'review']))
            ->whereNotExists(function (Builder $childQuery) use ($tasksTable): void {
                $childQuery
                    ->selectRaw('1')
                    ->from("{$tasksTable} as child_tasks")
                    ->whereColumn('child_tasks.parent_task_id', "{$tasksTable}.id");
            })
            ->where(function (Builder $scheduleQuery) use ($tasksTable, $period): void {
                $scheduleQuery
                    ->whereNull("{$tasksTable}.start_date")
                    ->orWhereNull("{$tasksTable}.due_date")
                    ->orWhere(function (Builder $overlapQuery) use ($tasksTable, $period): void {
                        $overlapQuery
                            ->whereDate("{$tasksTable}.start_date", '<=', $period['end'])
                            ->whereDate("{$tasksTable}.due_date", '>=', $period['start']);
                    });
            })
            ->select([
                'workload_assignments.user_id',
                'workload_assignments.task_id',
                "{$tasksTable}.project_id",
                'assignment_counts.active_assignee_count',
            ])
            ->selectRaw(
                "CASE WHEN {$tasksTable}.start_date IS NOT NULL AND {$tasksTable}.due_date IS NOT NULL THEN (1.0 / assignment_counts.active_assignee_count) ELSE 0 END as contribution",
            )
            ->selectRaw(
                "CASE WHEN {$tasksTable}.start_date IS NOT NULL AND {$tasksTable}.due_date IS NOT NULL THEN 1 ELSE 0 END as is_scheduled",
            )
            ->selectRaw(
                "CASE WHEN {$tasksTable}.start_date IS NOT NULL AND {$tasksTable}.due_date < ? THEN 1 ELSE 0 END as is_overdue",
                [CarbonImmutable::now(config('app.timezone'))->toDateString()],
            );
    }

    /**
     * @param  Collection<int, int>  $projectIds
     */
    private function assignmentQuery(Collection $projectIds): Builder
    {
        $tasksTable = (new Task)->getTable();
        $usersTable = (new User)->getTable();

        $pivotAssignments = DB::query()
            ->from('task_assignees')
            ->join($tasksTable, "{$tasksTable}.id", '=', 'task_assignees.task_id')
            ->join($usersTable, "{$usersTable}.id", '=', 'task_assignees.user_id')
            ->join('project_members', function ($join) use ($tasksTable): void {
                $join->on('project_members.project_id', '=', "{$tasksTable}.project_id")
                    ->on('project_members.user_id', '=', 'task_assignees.user_id');
            })
            ->where("{$usersTable}.is_active", true)
            ->whereIn("{$tasksTable}.project_id", $projectIds)
            ->selectRaw('DISTINCT task_assignees.task_id, task_assignees.user_id');

        $legacyAssignments = DB::query()
            ->from($tasksTable)
            ->join($usersTable, "{$usersTable}.id", '=', "{$tasksTable}.assignee_id")
            ->join('project_members', function ($join) use ($tasksTable): void {
                $join->on('project_members.project_id', '=', "{$tasksTable}.project_id")
                    ->on('project_members.user_id', '=', "{$tasksTable}.assignee_id");
            })
            ->where("{$usersTable}.is_active", true)
            ->whereIn("{$tasksTable}.project_id", $projectIds)
            ->whereNotExists(function (Builder $pivotQuery) use ($tasksTable): void {
                $pivotQuery
                    ->selectRaw('1')
                    ->from('task_assignees')
                    ->whereColumn('task_assignees.task_id', "{$tasksTable}.id");
            })
            ->selectRaw("{$tasksTable}.id as task_id, {$tasksTable}.assignee_id as user_id");

        return $pivotAssignments->union($legacyAssignments);
    }

    private function applyLevelFilter(EloquentBuilder $query, ?string $level): void
    {
        if (! $level) {
            return;
        }

        $scoreExpression = 'COALESCE(workload_totals.workload_score, 0)';
        $thresholds = $this->thresholds();

        match ($level) {
            'normal' => $query->whereRaw("{$scoreExpression} < ?", [$thresholds['attention']]),
            'attention' => $query
                ->whereRaw("{$scoreExpression} >= ?", [$thresholds['attention']])
                ->whereRaw("{$scoreExpression} < ?", [$thresholds['high_risk']]),
            'high_risk' => $query
                ->whereRaw("{$scoreExpression} >= ?", [$thresholds['high_risk']])
                ->whereRaw("{$scoreExpression} < ?", [$thresholds['critical']]),
            'critical' => $query->whereRaw("{$scoreExpression} >= ?", [$thresholds['critical']]),
        };
    }

    /**
     * @return array<string, int>
     */
    private function summary(EloquentBuilder $memberQuery): array
    {
        $thresholds = $this->thresholds();
        $summary = DB::query()
            ->fromSub($memberQuery->toBase(), 'workload_members')
            ->selectRaw('COUNT(*) as total_members')
            ->selectRaw('SUM(CASE WHEN workload_score < ? THEN 1 ELSE 0 END) as normal_count', [$thresholds['attention']])
            ->selectRaw('SUM(CASE WHEN workload_score >= ? AND workload_score < ? THEN 1 ELSE 0 END) as attention_count', [$thresholds['attention'], $thresholds['high_risk']])
            ->selectRaw('SUM(CASE WHEN workload_score >= ? AND workload_score < ? THEN 1 ELSE 0 END) as high_risk_count', [$thresholds['high_risk'], $thresholds['critical']])
            ->selectRaw('SUM(CASE WHEN workload_score >= ? THEN 1 ELSE 0 END) as critical_count', [$thresholds['critical']])
            ->selectRaw('COALESCE(SUM(overdue_count), 0) as overdue_count')
            ->selectRaw('COALESCE(SUM(unscheduled_count), 0) as unscheduled_count')
            ->first();

        return [
            'total_members' => (int) ($summary->total_members ?? 0),
            'normal_count' => (int) ($summary->normal_count ?? 0),
            'attention_count' => (int) ($summary->attention_count ?? 0),
            'high_risk_count' => (int) ($summary->high_risk_count ?? 0),
            'critical_count' => (int) ($summary->critical_count ?? 0),
            'overdue_count' => (int) ($summary->overdue_count ?? 0),
            'unscheduled_count' => (int) ($summary->unscheduled_count ?? 0),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function presentMember(User $member): array
    {
        $score = (float) $member->workload_score;
        $level = $this->levelForScore($score);
        $levelDetails = $this->levels()[$level];

        return [
            'id' => (int) $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'employee_id' => $member->employee_id,
            'job_title' => $member->job_title,
            'profile_photo' => $member->profile_photo,
            'initial' => mb_strtoupper(mb_substr($member->name, 0, 1)),
            'score' => round($score, 2),
            'level' => $level,
            'level_label' => $levelDetails['label'],
            'level_badge' => $levelDetails['badge'],
            'level_dot' => $levelDetails['dot'],
            'contributing_task_count' => (int) $member->contributing_task_count,
            'contributing_project_count' => (int) $member->contributing_project_count,
            'overdue_count' => (int) $member->overdue_count,
            'unscheduled_count' => (int) $member->unscheduled_count,
            'reason' => $this->reason(
                $score,
                (int) $member->contributing_task_count,
                (int) $member->contributing_project_count,
            ),
        ];
    }

    private function reason(float $score, int $taskCount, int $projectCount): string
    {
        if ($taskCount === 0) {
            return 'Tidak ada task terjadwal yang berkontribusi pada periode ini.';
        }

        return sprintf(
            'Skor %.2f berasal dari %d task aktif pada %d project dalam periode terpilih.',
            $score,
            $taskCount,
            $projectCount,
        );
    }

    /**
     * @return Collection<int, Workspace>
     */
    private function workspaceOptions(
        User $actor,
        string $scope,
        Collection $authorizedProjectIds,
    ): Collection {
        return Workspace::query()
            ->whereIn('id', $this->filterableWorkspaceIds($actor, $scope, $authorizedProjectIds))
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * @param  Collection<int, int>  $authorizedProjectIds
     * @return Collection<int, int>
     */
    private function filterableWorkspaceIds(
        User $actor,
        string $scope,
        Collection $authorizedProjectIds,
    ): Collection {
        if ($this->isGlobalScope($actor, $scope)) {
            return Workspace::query()
                ->pluck('id')
                ->map(fn ($id): int => (int) $id);
        }

        if ($actor->isSuperAdmin()) {
            return $this->accessibleWorkspaceIdsForManagedScope($actor);
        }

        return Workspace::query()
            ->whereHas('projects', fn (EloquentBuilder $query): EloquentBuilder => $query
                ->whereIn('id', $authorizedProjectIds))
            ->pluck('id')
            ->map(fn ($id): int => (int) $id);
    }

    /**
     * @param  Collection<int, int>  $authorizedProjectIds
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Project>
     */
    private function projectOptions(Collection $authorizedProjectIds, array $filters): Collection
    {
        return Project::query()
            ->whereIn('id', $authorizedProjectIds)
            ->when($filters['workspace'], fn (EloquentBuilder $query, int $workspaceId): EloquentBuilder => $query->where('workspace_id', $workspaceId))
            ->orderBy('name')
            ->get(['id', 'workspace_id', 'name']);
    }
}
