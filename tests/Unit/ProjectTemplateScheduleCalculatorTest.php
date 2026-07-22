<?php

namespace Tests\Unit;

use App\Models\ProjectTemplateTask;
use App\Models\ProjectTemplateTaskDependency;
use App\Services\ProjectTemplateScheduleCalculator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProjectTemplateScheduleCalculatorTest extends TestCase
{
    private ProjectTemplateScheduleCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->calculator = app(ProjectTemplateScheduleCalculator::class);
    }

    public function test_base_offsets_durations_and_weekends_use_inclusive_calendar_days(): void
    {
        $oneDay = $this->task(1, 0, 1);
        $fiveDays = $this->task(2, 3, 5);

        $schedule = $this->calculator->calculate('2026-07-24', collect([$oneDay, $fiveDays]));

        $this->assertSame('2026-07-24', $schedule[1]['start_date']->toDateString());
        $this->assertSame('2026-07-24', $schedule[1]['due_date']->toDateString());
        $this->assertSame('2026-07-27', $schedule[2]['start_date']->toDateString());
        $this->assertSame('2026-07-31', $schedule[2]['due_date']->toDateString());
    }

    #[DataProvider('dependencyProvider')]
    public function test_dependency_formulas(string $type, int $lag, string $expectedStart, string $expectedDue): void
    {
        $predecessor = $this->task(1, 0, 3);
        $successor = $this->task(2, 0, 2, 1, $type, $lag);

        $schedule = $this->calculator->calculate('2026-07-20', collect([$predecessor, $successor]));

        $this->assertSame($expectedStart, $schedule[2]['start_date']->toDateString());
        $this->assertSame($expectedDue, $schedule[2]['due_date']->toDateString());
    }

    public static function dependencyProvider(): array
    {
        return [
            'FS lag 0' => ['FS', 0, '2026-07-23', '2026-07-24'],
            'FS lag 2' => ['FS', 2, '2026-07-25', '2026-07-26'],
            'SS' => ['SS', 1, '2026-07-21', '2026-07-22'],
            'FF' => ['FF', 1, '2026-07-22', '2026-07-23'],
            'SF' => ['SF', 3, '2026-07-22', '2026-07-23'],
        ];
    }

    public function test_dependencies_never_move_a_task_before_its_base_offset_and_support_chains(): void
    {
        $first = $this->task(1, 0, 2);
        $second = $this->task(2, 10, 1, 1, 'FS');
        $third = $this->task(3, 0, 2, 2, 'FS');
        $disconnected = $this->task(4, 2, 1);

        $schedule = $this->calculator->calculate('2026-07-20', collect([$first, $second, $third, $disconnected]));

        $this->assertSame('2026-07-30', $schedule[2]['start_date']->toDateString());
        $this->assertSame('2026-07-31', $schedule[3]['start_date']->toDateString());
        $this->assertSame('2026-07-22', $schedule[4]['start_date']->toDateString());
    }

    public function test_cycle_is_rejected(): void
    {
        $first = $this->task(1, 0, 1, 2, 'FS');
        $second = $this->task(2, 0, 1, 1, 'FS');

        $this->expectException(ValidationException::class);

        $this->calculator->calculate('2026-07-20', collect([$first, $second]));
    }

    private function task(
        int $id,
        int $offset,
        int $duration,
        ?int $predecessorId = null,
        string $type = 'FS',
        int $lag = 0,
    ): ProjectTemplateTask {
        $task = new ProjectTemplateTask([
            'project_template_id' => 1,
            'name' => "Task {$id}",
            'position' => $id,
            'start_offset_days' => $offset,
            'duration_days' => $duration,
            'weight' => 10,
        ]);
        $task->id = $id;
        $task->setRelation('dependency', null);

        if ($predecessorId !== null) {
            $dependency = new ProjectTemplateTaskDependency([
                'project_template_id' => 1,
                'project_template_task_id' => $id,
                'predecessor_template_task_id' => $predecessorId,
                'dependency_type' => $type,
                'lag_days' => $lag,
            ]);
            $task->setRelation('dependency', $dependency);
        }

        return $task;
    }
}
