<?php

namespace Tests\Feature;

use App\Http\Controllers\DashboardController;
use App\Models\Notification;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkloadService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesProjectTemplateTestSchema;
use Tests\TestCase;

class WorkloadMonitoringTest extends TestCase
{
    use CreatesProjectTemplateTestSchema;

    private WorkloadService $workloadService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->createProjectTemplateTestSchema();
        Schema::table('users', function (Blueprint $table): void {
            $table->string('employee_id')->nullable();
            $table->string('profile_photo')->nullable();
            $table->string('job_title')->nullable();
        });
        Schema::create('device_users', function (Blueprint $table): void {
            $table->id();
            $table->string('device_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });
        DB::connection()->getPdo()->sqliteCreateFunction(
            'FIELD',
            fn ($value, ...$values): int => ($position = array_search($value, $values, true)) === false
                ? 0
                : $position + 1,
        );
        Carbon::setTestNow('2026-07-29 09:00:00');
        $this->withoutVite();
        $this->workloadService = app(WorkloadService::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_super_admin_defaults_to_managed_scope_and_can_expand_to_all_system(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Global Admin']);
        $managedMember = User::factory()->create(['name' => 'Managed Member']);
        $foreignOwner = User::factory()->create();
        $foreignMember = User::factory()->create(['name' => 'Foreign Member']);
        $managedProject = $this->projectWithMembers($superAdmin, [
            $superAdmin->id => Project::ROLE_MANAGER,
            $managedMember->id => Project::ROLE_MEMBER,
        ]);
        $foreignProject = $this->projectWithMembers($foreignOwner, [
            $foreignMember->id => Project::ROLE_MEMBER,
        ]);
        $this->task($managedProject, [$superAdmin]);
        $this->task($managedProject, [$managedMember]);
        $this->task($foreignProject, [$foreignMember]);

        $managedResponse = $this->actingAs($superAdmin)
            ->get(route('overload.index'));

        $managedResponse
            ->assertOk()
            ->assertSee('Workload Monitoring')
            ->assertSee('Global Admin')
            ->assertSee('Managed Member')
            ->assertDontSee('Foreign Member')
            ->assertSee('Cakupan Saya')
            ->assertSee('Semua Sistem')
            ->assertDontSee('Saya Sendiri')
            ->assertDontSee('Semua Anggota')
            ->assertViewHas('filters', fn (array $filters): bool => $filters['scope'] === 'managed');

        $this->actingAs($superAdmin)
            ->get(route('overload.index', ['scope' => 'all']))
            ->assertOk()
            ->assertSee('Global Admin')
            ->assertSee('Managed Member')
            ->assertSee('Foreign Member');
    }

    public function test_legacy_me_and_invalid_scope_are_normalized_to_managed_scope(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $managedMember = User::factory()->create(['name' => 'Managed Through Workspace']);
        $foreignOwner = User::factory()->create();
        $foreignMember = User::factory()->create(['name' => 'Foreign Through Workspace']);
        $managedProject = $this->projectWithMembers($superAdmin, [
            $managedMember->id => Project::ROLE_MEMBER,
        ]);
        $foreignProject = $this->projectWithMembers($foreignOwner, [
            $foreignMember->id => Project::ROLE_MEMBER,
        ]);
        $this->task($managedProject, [$managedMember]);
        $this->task($foreignProject, [$foreignMember]);

        foreach (['me', 'invalid-scope'] as $scope) {
            $response = $this->actingAs($superAdmin)
                ->get(route('overload.index', ['scope' => $scope]));

            $response
                ->assertOk()
                ->assertSee('Managed Through Workspace')
                ->assertDontSee('Foreign Through Workspace')
                ->assertViewHas('filters', fn (array $filters): bool => $filters['scope'] === 'managed');
        }

        $this->assertSame('managed', $this->workloadService->resolveScope($superAdmin, 'me'));
        $this->assertSame('managed', $this->workloadService->resolveScope($superAdmin, 'invalid-scope'));
    }

    public function test_super_admin_managed_scope_uses_normal_relationship_visibility_for_index_summary_and_detail(): void
    {
        $superAdmin = User::factory()->superAdmin()->create(['name' => 'Managed Super Admin']);
        $workspaceOwner = User::factory()->create();
        $joinedProjectOwner = User::factory()->create();
        $foreignOwner = User::factory()->create();
        $sharedTarget = User::factory()->create(['name' => 'Shared Scope Target']);
        $foreignOnlyTarget = User::factory()->create(['name' => 'Foreign Only Target']);

        $managedWorkspace = Workspace::factory()->for($workspaceOwner, 'creator')->create([
            'name' => 'Admin Managed Workspace',
        ]);
        $managedWorkspace->members()->attach($superAdmin, [
            'role' => Workspace::ROLE_ADMIN,
            'joined_at' => now(),
        ]);
        $managedProject = $this->projectWithMembers($workspaceOwner, [
            $sharedTarget->id => Project::ROLE_MEMBER,
        ], $managedWorkspace);

        $joinedWorkspace = Workspace::factory()->for($joinedProjectOwner, 'creator')->create([
            'name' => 'Joined Project Workspace',
        ]);
        $joinedProject = $this->projectWithMembers($joinedProjectOwner, [
            $superAdmin->id => Project::ROLE_MANAGER,
            $sharedTarget->id => Project::ROLE_MEMBER,
        ], $joinedWorkspace);

        $foreignWorkspace = Workspace::factory()->for($foreignOwner, 'creator')->create([
            'name' => 'Foreign Workspace',
        ]);
        $foreignProject = $this->projectWithMembers($foreignOwner, [
            $sharedTarget->id => Project::ROLE_MEMBER,
            $foreignOnlyTarget->id => Project::ROLE_MEMBER,
        ], $foreignWorkspace);

        $this->task($managedProject, [$sharedTarget]);
        $this->task($joinedProject, [$sharedTarget]);
        $this->task($joinedProject, [$superAdmin]);
        $this->task($foreignProject, [$sharedTarget]);
        $this->task($foreignProject, [$foreignOnlyTarget]);

        $managedResult = $this->workloadService->index($superAdmin, ['scope' => 'managed']);
        $managedTarget = collect($managedResult['members']->items())->firstWhere('id', $sharedTarget->id);

        $this->assertSame('managed', $managedResult['filters']['scope']);
        $this->assertSame(2.0, $managedTarget['score']);
        $this->assertSame(1, collect($managedResult['members']->items())->where('id', $sharedTarget->id)->count());
        $this->assertSame(2, $managedResult['summary']['total_members']);
        $this->assertNotContains($foreignOnlyTarget->id, collect($managedResult['members']->items())->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$managedProject->id, $joinedProject->id],
            $managedResult['projects']->pluck('id')->all(),
        );
        $this->assertSame([$managedWorkspace->id], $managedResult['workspaces']->pluck('id')->all());

        $managedDetail = $this->workloadService->detail($superAdmin, $sharedTarget, ['scope' => 'managed']);
        $this->assertCount(2, $managedDetail['projects']);
        $this->assertNotContains($foreignProject->id, $managedDetail['projects']->pluck('id')->all());

        $allResult = $this->workloadService->index($superAdmin, ['scope' => 'all']);
        $allTarget = collect($allResult['members']->items())->firstWhere('id', $sharedTarget->id);

        $this->assertSame('all', $allResult['filters']['scope']);
        $this->assertSame(3.0, $allTarget['score']);
        $this->assertSame(3, $allResult['summary']['total_members']);
        $this->assertContains($foreignOnlyTarget->id, collect($allResult['members']->items())->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$managedWorkspace->id, $joinedWorkspace->id, $foreignWorkspace->id],
            $allResult['workspaces']->pluck('id')->all(),
        );

        $allDetail = $this->workloadService->detail($superAdmin, $sharedTarget, ['scope' => 'all']);
        $this->assertCount(3, $allDetail['projects']);
        $this->assertContains($foreignProject->id, $allDetail['projects']->pluck('id')->all());

        $allWorkspaceResult = $this->workloadService->index($superAdmin, [
            'scope' => 'all',
            'workspace' => $foreignWorkspace->id,
        ]);
        $this->assertSame([$foreignProject->id], $allWorkspaceResult['projects']->pluck('id')->all());
        $this->assertContains($foreignOnlyTarget->id, collect($allWorkspaceResult['members']->items())->pluck('id')->all());
        $this->assertNotContains($superAdmin->id, collect($allWorkspaceResult['members']->items())->pluck('id')->all());
    }

    public function test_super_admin_managed_scope_rejects_stale_workspace_and_project_filters(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $managedMember = User::factory()->create(['name' => 'Managed Filter Member']);
        $foreignOwner = User::factory()->create();
        $foreignMember = User::factory()->create(['name' => 'Foreign Filter Member']);
        $managedProject = $this->projectWithMembers($superAdmin, [
            $managedMember->id => Project::ROLE_MEMBER,
        ]);
        $foreignProject = $this->projectWithMembers($foreignOwner, [
            $foreignMember->id => Project::ROLE_MEMBER,
        ]);
        $this->task($managedProject, [$managedMember]);
        $this->task($foreignProject, [$foreignMember]);

        $staleWorkspaceResult = $this->workloadService->index($superAdmin, [
            'scope' => 'managed',
            'workspace' => $foreignProject->workspace_id,
        ]);
        $staleProjectResult = $this->workloadService->index($superAdmin, [
            'scope' => 'managed',
            'project' => $foreignProject->id,
        ]);

        $this->assertNull($staleWorkspaceResult['filters']['workspace']);
        $this->assertNull($staleProjectResult['filters']['project']);
        $this->assertSame(0, $staleWorkspaceResult['members']->total());
        $this->assertSame(0, $staleProjectResult['members']->total());
        $this->assertNotContains($foreignProject->workspace_id, $staleWorkspaceResult['workspaces']->pluck('id')->all());
        $this->assertNotContains($foreignProject->id, $staleProjectResult['projects']->pluck('id')->all());
    }

    public function test_dashboard_workload_summary_uses_normal_state_and_default_period(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor]);

        $summary = $this->workloadService->dashboardSummary($actor);

        $this->assertSame(1, $summary['normal_count']);
        $this->assertSame(0, $summary['affected_count']);
        $this->assertSame('normal', $summary['highest_level']);
        $this->assertSame('7 Hari ke Depan', $summary['period_label']);
        $this->assertReportUrl($summary['report_url'], null);

        $this->view('dashboard.partials._workload-link', ['workloadSummary' => $summary])
            ->assertSee('data-workload-level="normal"', false)
            ->assertSee('Workload Monitoring')
            ->assertSee('Tidak ada risiko beban tugas pada periode 7 Hari ke Depan.')
            ->assertSee('Berdasarkan Skor Beban Tugas untuk 7 Hari ke Depan.')
            ->assertSee('bg-sky-50/70', false)
            ->assertDontSee('bg-gradient', false);
    }

    #[DataProvider('dashboardRiskLevelProvider')]
    public function test_dashboard_workload_summary_uses_highest_single_risk_state_and_filtered_cta(
        int $taskCount,
        string $level,
        string $title,
        string $tone,
    ): void {
        [$actor, $project] = $this->actorProject();

        for ($taskIndex = 0; $taskIndex < $taskCount; $taskIndex++) {
            $this->task($project, [$actor]);
        }

        $summary = $this->workloadService->dashboardSummary($actor);

        $this->assertSame((float) $taskCount, $this->memberResult($actor)['score']);
        $this->assertSame(1, $summary[$level.'_count']);
        $this->assertSame(1, $summary['affected_count']);
        $this->assertSame($level, $summary['highest_level']);
        $this->assertReportUrl($summary['report_url'], $level);

        $this->view('dashboard.partials._workload-link', ['workloadSummary' => $summary])
            ->assertSee($title)
            ->assertSee($tone, false)
            ->assertSee('1 anggota');
    }

    /**
     * @return array<string, array{int, string, string, string}>
     */
    public static function dashboardRiskLevelProvider(): array
    {
        return [
            'attention' => [5, 'attention', 'Workload Perlu Perhatian', 'bg-amber-50/70'],
            'high risk' => [7, 'high_risk', 'Risiko Beban Tugas Tinggi', 'bg-orange-50/70'],
            'critical' => [9, 'critical', 'Workload Kritis', 'bg-red-50/70'],
        ];
    }

    public function test_dashboard_workload_summary_counts_distinct_users_and_shows_multiple_levels(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $attentionMember = User::factory()->create();
        $highRiskMember = User::factory()->create();
        $criticalMember = User::factory()->create();
        $project = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $attentionMember->id => Project::ROLE_MEMBER,
            $highRiskMember->id => Project::ROLE_MEMBER,
            $criticalMember->id => Project::ROLE_MEMBER,
        ]);

        foreach ([
            [$attentionMember, 5],
            [$highRiskMember, 7],
            [$criticalMember, 9],
        ] as [$member, $taskCount]) {
            for ($taskIndex = 0; $taskIndex < $taskCount; $taskIndex++) {
                $this->task($project, [$member]);
            }
        }

        $summary = $this->workloadService->dashboardSummary($actor);

        $this->assertSame(1, $summary['normal_count']);
        $this->assertSame(1, $summary['attention_count']);
        $this->assertSame(1, $summary['high_risk_count']);
        $this->assertSame(1, $summary['critical_count']);
        $this->assertSame(3, $summary['affected_count']);
        $this->assertSame('critical', $summary['highest_level']);
        $this->assertReportUrl($summary['report_url'], null);

        $this->view('dashboard.partials._workload-link', ['workloadSummary' => $summary])
            ->assertSee('1 Kritis ·')
            ->assertSee('1 Risiko Tinggi ·')
            ->assertSee('1 Perlu Perhatian');
    }

    public function test_dashboard_workload_summary_uses_managed_scope_for_super_admin_and_shared_scope_for_member(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $managedMember = User::factory()->create();
        $foreignOwner = User::factory()->create();
        $foreignMember = User::factory()->create();
        $managedProject = $this->projectWithMembers($superAdmin, [
            $managedMember->id => Project::ROLE_MEMBER,
        ]);
        $foreignProject = $this->projectWithMembers($foreignOwner, [
            $foreignMember->id => Project::ROLE_MEMBER,
        ]);

        for ($taskIndex = 0; $taskIndex < 5; $taskIndex++) {
            $this->task($managedProject, [$managedMember]);
        }
        for ($taskIndex = 0; $taskIndex < 9; $taskIndex++) {
            $this->task($foreignProject, [$foreignMember]);
        }

        $superAdminSummary = $this->workloadService->dashboardSummary($superAdmin);
        $this->assertSame(1, $superAdminSummary['attention_count']);
        $this->assertSame(0, $superAdminSummary['critical_count']);
        $this->assertSame('attention', $superAdminSummary['highest_level']);

        $member = User::factory()->create();
        $sharedMember = User::factory()->create();
        $sharedProject = $this->projectWithMembers($foreignOwner, [
            $member->id => Project::ROLE_MEMBER,
            $sharedMember->id => Project::ROLE_MEMBER,
        ]);
        for ($taskIndex = 0; $taskIndex < 5; $taskIndex++) {
            $this->task($sharedProject, [$sharedMember]);
        }

        $memberSummary = $this->workloadService->dashboardSummary($member);
        $this->assertSame(1, $memberSummary['attention_count']);
        $this->assertSame(0, $memberSummary['critical_count']);
        $this->assertSame(2, $memberSummary['normal_count'] + $memberSummary['affected_count']);
    }

    public function test_dashboard_viewer_does_not_receive_card_or_execute_workload_summary(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $this->projectWithMembers($owner, [$viewer->id => Project::ROLE_VIEWER]);
        $this->actingAs($viewer);

        $workloadService = \Mockery::mock(WorkloadService::class);
        $workloadService->shouldReceive('canView')->once()->with($viewer)->andReturnFalse();
        $workloadService->shouldNotReceive('dashboardSummary');

        $view = (new DashboardController($workloadService))->index();

        $this->assertNull($view->getData()['workloadSummary']);
        $this->view('dashboard.partials._workload-link', ['workloadSummary' => null])
            ->assertDontSee('data-dashboard-workload', false);
    }

    public function test_dashboard_get_is_read_only_for_notifications_mail_queue_device_and_workload_cache(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor], [
            'priority' => 'urgent',
            'due_date' => '2026-07-29',
        ]);
        $notification = Notification::query()->create([
            'user_id' => $actor->id,
            'type' => 'member_overload',
            'title' => 'Existing workload notification',
            'message' => 'Must remain unchanged',
        ]);
        Cache::forget("deadline_sent_{$actor->id}");
        Cache::put("overload_sent_{$actor->id}", 'unchanged');
        Mail::fake();
        Queue::fake();
        $before = $this->databaseSnapshot();
        $deviceCount = DB::table('device_users')->count();

        $this->actingAs($actor)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-dashboard-workload', false);

        $this->assertSame($before, $this->databaseSnapshot());
        $this->assertSame($deviceCount, DB::table('device_users')->count());
        $this->assertSame('Must remain unchanged', $notification->fresh()->message);
        $this->assertFalse(Cache::has("deadline_sent_{$actor->id}"));
        $this->assertSame('unchanged', Cache::get("overload_sent_{$actor->id}"));
        Mail::assertNothingOutgoing();
        Queue::assertNothingPushed();
    }

    public function test_dashboard_workload_summary_query_count_is_stable_as_members_and_tasks_grow(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->workloadService->dashboardSummary($actor);
        $smallQueryCount = count(DB::getQueryLog());

        $additionalMembers = User::factory()->count(20)->create();
        foreach ($additionalMembers as $member) {
            $project->members()->attach($member, [
                'role' => Project::ROLE_MEMBER,
                'joined_at' => now(),
            ]);
            $this->task($project, [$member]);
        }

        DB::flushQueryLog();
        $this->workloadService->dashboardSummary($actor);
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($smallQueryCount, $largeQueryCount);
    }

    public function test_workspace_admin_project_admin_and_member_only_see_authorized_shared_scope(): void
    {
        $owner = User::factory()->create(['name' => 'Workspace Owner']);
        $workspaceAdmin = User::factory()->create(['name' => 'Workspace Admin']);
        $projectAdmin = User::factory()->create(['name' => 'Project Admin']);
        $member = User::factory()->create(['name' => 'Shared Member']);
        $sharedTarget = User::factory()->create(['name' => 'Shared Target']);
        $outsideTarget = User::factory()->create(['name' => 'Outside Target']);
        $workspace = Workspace::factory()->for($owner, 'creator')->create();
        $workspace->members()->attach($workspaceAdmin, [
            'role' => Workspace::ROLE_ADMIN,
            'joined_at' => now(),
        ]);
        $sharedProject = $this->projectWithMembers($owner, [
            $workspaceAdmin->id => Project::ROLE_VIEWER,
            $projectAdmin->id => Project::ROLE_MANAGER,
            $member->id => Project::ROLE_MEMBER,
            $sharedTarget->id => Project::ROLE_MEMBER,
        ], $workspace);
        $outsideProject = $this->projectWithMembers($owner, [
            $outsideTarget->id => Project::ROLE_MEMBER,
        ]);
        $this->task($sharedProject, [$sharedTarget]);
        $this->task($outsideProject, [$outsideTarget]);

        $this->actingAs($workspaceAdmin)
            ->get(route('overload.index'))
            ->assertOk()
            ->assertSee('Shared Target')
            ->assertDontSee('Outside Target');

        foreach ([$projectAdmin, $member] as $actor) {
            $this->actingAs($actor)
                ->get(route('overload.index', ['scope' => 'all']))
                ->assertOk()
                ->assertSee('Shared Target')
                ->assertDontSee('Outside Target')
                ->assertDontSee('name="scope"', false);
        }
    }

    public function test_viewer_is_forbidden_and_sidebar_hides_workload_menu(): void
    {
        $owner = User::factory()->create();
        $viewer = User::factory()->create();
        $project = $this->projectWithMembers($owner, [$viewer->id => Project::ROLE_VIEWER]);

        $this->actingAs($viewer)
            ->get(route('overload.index'))
            ->assertForbidden();

        $view = $this->actingAs($viewer)->view('layouts.sidebar');
        $view->assertDontSee('Workload Monitoring');
        $this->assertNotNull($project);
    }

    public function test_scope_tampering_never_expands_workspace_project_or_member_detail(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $sharedTarget = User::factory()->create(['name' => 'Visible Target']);
        $outsideTarget = User::factory()->create(['name' => 'Hidden Target']);
        $sharedProject = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $sharedTarget->id => Project::ROLE_MEMBER,
        ]);
        $outsideProject = $this->projectWithMembers($owner, [
            $outsideTarget->id => Project::ROLE_MEMBER,
        ]);
        $this->task($sharedProject, [$sharedTarget]);
        $this->task($outsideProject, [$outsideTarget]);

        $this->actingAs($actor)
            ->get(route('overload.index', ['workspace' => $outsideProject->workspace_id]))
            ->assertOk()
            ->assertDontSee('Hidden Target')
            ->assertDontSee('Visible Target');

        $this->actingAs($actor)
            ->get(route('overload.index', ['project' => $outsideProject->id]))
            ->assertOk()
            ->assertDontSee('Hidden Target');

        $this->actingAs($actor)
            ->getJson(route('overload.members.show', $outsideTarget))
            ->assertNotFound();
    }

    public function test_same_user_across_shared_projects_is_aggregated_once_with_shared_breakdown_only(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $target = User::factory()->create(['name' => 'One Aggregated User']);
        $sharedOne = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $target->id => Project::ROLE_MEMBER,
        ]);
        $sharedTwo = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $target->id => Project::ROLE_MEMBER,
        ]);
        $outside = $this->projectWithMembers($owner, [$target->id => Project::ROLE_MEMBER]);
        $this->task($sharedOne, [$target]);
        $this->task($sharedTwo, [$target]);
        $this->task($outside, [$target]);

        $result = $this->workloadService->index($actor, []);
        $member = collect($result['members']->items())->firstWhere('id', $target->id);

        $this->assertSame(1, collect($result['members']->items())->where('id', $target->id)->count());
        $this->assertSame(2.0, $member['score']);
        $this->assertSame(2, $member['contributing_project_count']);

        $detail = $this->workloadService->detail($actor, $target, []);
        $this->assertCount(2, $detail['projects']);
        $this->assertNotContains($outside->id, $detail['projects']->pluck('id')->all());
    }

    public function test_get_is_read_only_and_does_not_dispatch_mail_queue_or_touch_overload_cache(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $project = $this->projectWithMembers($owner, [$actor->id => Project::ROLE_MEMBER]);
        $this->task($project, [$actor]);
        $notification = Notification::query()->create([
            'user_id' => $actor->id,
            'type' => 'member_overload',
            'title' => 'Legacy overload',
            'message' => 'Must remain unchanged',
        ]);
        Cache::put("overload_sent_{$actor->id}", 'legacy-state');
        Mail::fake();
        Queue::fake();
        $before = $this->databaseSnapshot();

        $this->actingAs($actor)
            ->get(route('overload.index'))
            ->assertOk();

        $this->assertSame($before, $this->databaseSnapshot());
        $this->assertSame('legacy-state', Cache::get("overload_sent_{$actor->id}"));
        $this->assertSame('Must remain unchanged', $notification->fresh()->message);
        Mail::assertNothingSent();
        Queue::assertNothingPushed();
    }

    #[DataProvider('activeAndExcludedStatusProvider')]
    public function test_only_central_active_task_statuses_contribute(string $status, float $expectedScore): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor], ['status' => $status]);

        $this->assertSame($expectedScore, $this->memberResult($actor)['score']);
    }

    public static function activeAndExcludedStatusProvider(): array
    {
        return [
            'to do' => ['to_do', 1.0],
            'in progress' => ['in_progress', 1.0],
            'review' => ['review', 1.0],
            'completed' => ['completed', 0.0],
            'cancelled' => ['cancelled', 0.0],
            'stopped' => ['stopped', 0.0],
            'unknown' => ['unexpected_status', 0.0],
        ];
    }

    #[DataProvider('projectStatusProvider')]
    public function test_only_operational_project_statuses_contribute(string $status, float $expectedScore): void
    {
        [$actor, $project] = $this->actorProject($status);
        $this->task($project, [$actor]);

        $result = $this->workloadService->index($actor, []);
        $member = collect($result['members']->items())->firstWhere('id', $actor->id);

        if ($expectedScore === 0.0) {
            $this->assertNull($member);
        } else {
            $this->assertSame($expectedScore, $member['score']);
        }
    }

    public static function projectStatusProvider(): array
    {
        return [
            'active' => ['active', 1.0],
            'urgent' => ['urgent', 1.0],
            'planning' => ['planning', 0.0],
            'on hold' => ['on_hold', 0.0],
            'completed' => ['completed', 0.0],
            'cancelled' => ['cancelled', 0.0],
        ];
    }

    public function test_period_overlap_boundaries_exclusions_and_default_period_are_correct(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor], ['name' => 'Overlap', 'start_date' => '2026-07-29', 'due_date' => '2026-08-04']);
        $this->task($project, [$actor], ['name' => 'Ends on start', 'start_date' => '2026-07-20', 'due_date' => '2026-07-29']);
        $this->task($project, [$actor], ['name' => 'Starts on end', 'start_date' => '2026-08-04', 'due_date' => '2026-08-10']);
        $this->task($project, [$actor], ['name' => 'Before', 'start_date' => '2026-07-01', 'due_date' => '2026-07-28']);
        $this->task($project, [$actor], ['name' => 'After', 'start_date' => '2026-08-05', 'due_date' => '2026-08-10']);

        $result = $this->workloadService->index($actor, []);

        $this->assertSame('next_7_days', $result['period']['key']);
        $this->assertSame('7 Hari ke Depan', $result['period']['label']);
        $this->assertSame('2026-07-29', $result['period']['start']);
        $this->assertSame('2026-08-04', $result['period']['end']);
        $this->assertSame(3.0, $this->memberResult($actor)['score']);
    }

    public function test_week_month_and_custom_period_filters_work_and_invalid_custom_range_is_rejected(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor], ['start_date' => '2026-07-01', 'due_date' => '2026-07-31']);

        $this->assertSame(1.0, $this->memberResult($actor, ['period' => 'this_week'])['score']);
        $this->assertSame(1.0, $this->memberResult($actor, ['period' => 'this_month'])['score']);
        $this->assertSame(1.0, $this->memberResult($actor, [
            'period' => 'custom',
            'start_date' => '2026-07-10',
            'end_date' => '2026-07-12',
        ])['score']);

        $this->actingAs($actor)
            ->get(route('overload.index', [
                'period' => 'custom',
                'start_date' => '2026-08-10',
                'end_date' => '2026-08-01',
            ]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_unscheduled_and_overdue_are_separate_without_score_bonus(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor], ['start_date' => null, 'due_date' => '2026-08-01']);
        $this->task($project, [$actor], ['start_date' => '2026-07-29', 'due_date' => null]);
        $this->task($project, [$actor], ['start_date' => '2026-07-20', 'due_date' => '2026-07-29']);
        $this->task($project, [$actor], ['start_date' => '2026-07-20', 'due_date' => '2026-07-28']);

        $member = $this->memberResult($actor, [
            'period' => 'custom',
            'start_date' => '2026-07-20',
            'end_date' => '2026-07-29',
        ]);

        $this->assertSame(2.0, $member['score']);
        $this->assertSame(1, $member['overdue_count']);
        $this->assertSame(2, $member['unscheduled_count']);
    }

    public function test_leaf_only_dependency_has_no_bonus_and_parent_container_is_excluded(): void
    {
        [$actor, $project] = $this->actorProject();
        $predecessor = $this->task($project, [$actor], ['name' => 'Predecessor']);
        $parent = $this->task($project, [$actor], ['name' => 'Parent']);
        $this->task($project, [$actor], [
            'name' => 'Leaf child',
            'parent_task_id' => $parent->id,
            'predecessor_id' => $predecessor->id,
        ]);

        $this->assertSame(2.0, $this->memberResult($actor)['score']);
    }

    public function test_multi_assignee_uses_fractional_contribution_and_ignores_inactive_assignees(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $second = User::factory()->create();
        $third = User::factory()->create();
        $fourth = User::factory()->create();
        $inactive = User::factory()->create(['is_active' => false]);
        $project = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $second->id => Project::ROLE_MEMBER,
            $third->id => Project::ROLE_MEMBER,
            $fourth->id => Project::ROLE_MEMBER,
            $inactive->id => Project::ROLE_MEMBER,
        ]);
        $this->task($project, [$actor]);
        $this->task($project, [$actor, $second]);
        $this->task($project, [$actor, $second, $third, $fourth]);
        $this->task($project, [$actor, $inactive]);

        $this->assertSame(2.75, $this->memberResult($actor)['score']);
    }

    public function test_pivot_is_primary_legacy_is_fallback_and_assignment_is_not_double_counted(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $other = User::factory()->create();
        $project = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $other->id => Project::ROLE_MEMBER,
        ]);
        $this->task($project, [$actor], ['assignee_id' => $actor->id]);
        $this->task($project, [], ['assignee_id' => $actor->id]);
        $this->task($project, [$other], ['assignee_id' => $actor->id]);

        $this->assertSame(2.0, $this->memberResult($actor)['score']);
        $this->assertSame(1.0, $this->memberResult($other)['score']);
    }

    #[DataProvider('thresholdProvider')]
    public function test_level_boundaries_are_read_from_config(float $score, string $expectedLevel): void
    {
        $this->assertSame($expectedLevel, $this->workloadService->levelForScore($score));

        config()->set('atur.workload.thresholds', [
            'attention' => 10,
            'high_risk' => 20,
            'critical' => 30,
        ]);

        $this->assertSame('normal', $this->workloadService->levelForScore(9));
        $this->assertSame('attention', $this->workloadService->levelForScore(10));
    }

    public static function thresholdProvider(): array
    {
        return [
            'zero' => [0, 'normal'],
            'normal upper' => [4.99, 'normal'],
            'attention lower' => [5, 'attention'],
            'attention upper' => [6.99, 'attention'],
            'high risk lower' => [7, 'high_risk'],
            'high risk upper' => [8.99, 'high_risk'],
            'critical lower' => [9, 'critical'],
        ];
    }

    public function test_filters_search_level_summary_and_pagination_query_are_preserved(): void
    {
        config()->set('atur.workload.per_page', 1);
        $owner = User::factory()->create();
        $actor = User::factory()->create(['name' => 'Alpha Member', 'employee_id' => 'EMP-001']);
        $other = User::factory()->create(['name' => 'Beta Member', 'employee_id' => 'EMP-002']);
        $project = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $other->id => Project::ROLE_MEMBER,
        ]);
        $this->task($project, [$actor]);

        $response = $this->actingAs($actor)->get(route('overload.index', [
            'search' => 'EMP-00',
            'level' => 'normal',
            'project' => $project->id,
        ]));

        $response
            ->assertOk()
            ->assertSee('Alpha Member')
            ->assertDontSee('Beta Member')
            ->assertSee('search=EMP-00', false)
            ->assertSee('level=normal', false)
            ->assertSee('project='.$project->id, false)
            ->assertSee('scope=managed', false);
    }

    public function test_valid_workspace_and_project_filters_constrain_results(): void
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $firstTarget = User::factory()->create(['name' => 'First Workspace Target']);
        $secondTarget = User::factory()->create(['name' => 'Second Workspace Target']);
        $firstProject = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $firstTarget->id => Project::ROLE_MEMBER,
        ]);
        $secondProject = $this->projectWithMembers($owner, [
            $actor->id => Project::ROLE_MEMBER,
            $secondTarget->id => Project::ROLE_MEMBER,
        ]);
        $this->task($firstProject, [$firstTarget]);
        $this->task($secondProject, [$secondTarget]);

        $this->actingAs($actor)
            ->get(route('overload.index', ['workspace' => $firstProject->workspace_id]))
            ->assertOk()
            ->assertSee('First Workspace Target')
            ->assertDontSee('Second Workspace Target');

        $this->actingAs($actor)
            ->get(route('overload.index', ['project' => $secondProject->id]))
            ->assertOk()
            ->assertSee('Second Workspace Target')
            ->assertDontSee('First Workspace Target');
    }

    public function test_ui_explains_formula_has_accessible_modals_and_uses_external_safe_javascript(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor]);

        $response = $this->actingAs($actor)->get(route('overload.index'));

        $response
            ->assertOk()
            ->assertSee('Cara Perhitungan')
            ->assertSee('Lihat Perhitungan')
            ->assertSee('data-workload-page', false)
            ->assertSee('data-workload-disclaimer', false)
            ->assertSee('data-workload-desktop-table', false)
            ->assertSee('data-workload-mobile-list', false)
            ->assertSee('name="period"', false)
            ->assertSee('name="workspace"', false)
            ->assertSee('name="project"', false)
            ->assertSee('name="level"', false)
            ->assertSee('name="search"', false)
            ->assertSee('name="start_date"', false)
            ->assertSee('name="end_date"', false)
            ->assertSee('Normal')
            ->assertSee('Perlu Perhatian')
            ->assertSee('Risiko Tinggi')
            ->assertSee('Kritis')
            ->assertSee('Overdue')
            ->assertSee('Unscheduled')
            ->assertSee('Task Aktif')
            ->assertSee('Shared Project')
            ->assertSee('data-workload-detail-open', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-modal="true"', false)
            ->assertSee('data-workload-modal', false)
            ->assertSee('data-workload-modal-panel', false)
            ->assertSee('Story Point')
            ->assertSee('leaf task')
            ->assertSee('1 ÷ jumlah assignee aktif')
            ->assertSee(WorkloadService::DISCLAIMER)
            ->assertDontSee('{!!', false);

        $javascript = file_get_contents(resource_path('js/workload-monitoring.js'));
        $this->assertStringContainsString("event.key === 'Escape'", $javascript);
        $this->assertStringContainsString('returnFocusTarget?.focus()', $javascript);
        $this->assertStringContainsString('document.createElement', $javascript);
        $this->assertStringContainsString('document.createTextNode', $javascript);
        $this->assertStringNotContainsString('innerHTML', $javascript);

        $indexView = file_get_contents(resource_path('views/workload/index.blade.php'));
        $headerView = file_get_contents(resource_path('views/workload/partials/_header.blade.php'));
        $memberListView = file_get_contents(resource_path('views/workload/partials/_member-list.blade.php'));

        $this->assertStringContainsString('w-full px-4 py-4 md:px-8', $indexView);
        $this->assertStringNotContainsString('max-w-7xl', $indexView);
        $this->assertStringNotContainsString('bg-gradient', $headerView);
        $this->assertStringNotContainsString('shadow', $headerView);
        $this->assertStringContainsString('$members->onEachSide(1)->links()', $memberListView);
    }

    public function test_dashboard_and_project_member_card_no_longer_use_the_legacy_formula(): void
    {
        $dashboardController = file_get_contents(app_path('Http/Controllers/DashboardController.php'));
        $projectController = file_get_contents(app_path('Http/Controllers/ProjectController.php'));
        $dashboardView = file_get_contents(resource_path('views/dashboard/index.blade.php'));
        $projectMemberCard = file_get_contents(resource_path('views/projects/partials/show/members/_member-card.blade.php'));

        $this->assertStringNotContainsString('sendOverloadNotifications', $dashboardController);
        $this->assertStringNotContainsString('overloadedMembers', $dashboardController);
        $this->assertStringNotContainsString('memberTaskCounts', $projectController);
        $this->assertStringNotContainsString('Overload (', $projectMemberCard);
        $this->assertStringContainsString('dashboard.partials._workload-link', $dashboardView);
    }

    public function test_member_detail_returns_score_reason_projects_tasks_assignee_count_and_contribution(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor], ['name' => 'Detailed Task']);

        $this->actingAs($actor)
            ->getJson(route('overload.members.show', $actor))
            ->assertOk()
            ->assertJsonPath('member.score', 1)
            ->assertJsonPath('member.level', 'normal')
            ->assertJsonPath('projects.0.name', $project->name)
            ->assertJsonPath('contributing_tasks.0.name', 'Detailed Task')
            ->assertJsonPath('contributing_tasks.0.active_assignee_count', 1)
            ->assertJsonPath('contributing_tasks.0.contribution', 1)
            ->assertJsonFragment(['disclaimer' => WorkloadService::DISCLAIMER]);
    }

    public function test_query_count_is_stable_when_members_and_tasks_increase_and_pagination_is_server_side(): void
    {
        [$actor, $project] = $this->actorProject();
        $this->task($project, [$actor]);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $smallResult = $this->workloadService->index($actor, []);
        $smallQueryCount = count(DB::getQueryLog());

        $additionalMembers = User::factory()->count(20)->create();
        foreach ($additionalMembers as $member) {
            $project->members()->attach($member, [
                'role' => Project::ROLE_MEMBER,
                'joined_at' => now(),
            ]);
            $this->task($project, [$member]);
        }

        DB::flushQueryLog();
        $largeResult = $this->workloadService->index($actor, []);
        $largeQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual($smallQueryCount + 1, $largeQueryCount);
        $this->assertSame((int) config('atur.workload.per_page'), $largeResult['members']->perPage());
        $this->assertGreaterThan($largeResult['members']->count(), $largeResult['members']->total());
        $this->assertSame(2, $largeResult['members']->lastPage());
        $this->assertSame(1, $smallResult['members']->total());
    }

    /**
     * @return array{User, Project}
     */
    private function actorProject(string $projectStatus = 'active'): array
    {
        $owner = User::factory()->create();
        $actor = User::factory()->create();
        $project = $this->projectWithMembers(
            $owner,
            [$actor->id => Project::ROLE_MEMBER],
            status: $projectStatus,
        );

        return [$actor, $project];
    }

    /**
     * @param  array<int, string>  $members
     */
    private function projectWithMembers(
        User $owner,
        array $members,
        ?Workspace $workspace = null,
        string $status = 'active',
    ): Project {
        $workspace ??= Workspace::factory()->for($owner, 'creator')->create();
        $project = Project::factory()
            ->for($workspace)
            ->for($owner, 'creator')
            ->create(['status' => $status]);

        foreach ($members as $userId => $role) {
            $project->members()->attach($userId, [
                'role' => $role,
                'joined_at' => now(),
            ]);
        }

        return $project;
    }

    /**
     * @param  array<int, User>  $assignees
     * @param  array<string, mixed>  $attributes
     */
    private function task(Project $project, array $assignees, array $attributes = []): Task
    {
        $creator = $project->creator;
        $task = Task::factory()
            ->for($project)
            ->for($creator, 'creator')
            ->create([
                'start_date' => '2026-07-29',
                'due_date' => '2026-08-04',
                ...$attributes,
            ]);

        if ($assignees !== []) {
            $task->assignees()->attach(collect($assignees)->pluck('id'));
        }

        return $task;
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function memberResult(User $actor, array $filters = []): array
    {
        $result = $this->workloadService->index($actor, $filters);

        return collect($result['members']->items())->firstWhere('id', $actor->id);
    }

    private function assertReportUrl(string $url, ?string $expectedLevel): void
    {
        $this->assertSame(route('overload.index', absolute: false), parse_url($url, PHP_URL_PATH));
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);
        $this->assertSame('next_7_days', $query['period'] ?? null);

        if ($expectedLevel === null) {
            $this->assertArrayNotHasKey('level', $query);

            return;
        }

        $this->assertSame($expectedLevel, $query['level'] ?? null);
    }

    /**
     * @return array<string, int>
     */
    private function databaseSnapshot(): array
    {
        return [
            'users' => DB::table('users')->count(),
            'workspaces' => DB::table('workspaces')->count(),
            'projects' => DB::table('projects')->count(),
            'project_members' => DB::table('project_members')->count(),
            'tasks' => DB::table('tasks')->count(),
            'task_assignees' => DB::table('task_assignees')->count(),
            'notifications' => DB::table('notifications')->count(),
        ];
    }
}
