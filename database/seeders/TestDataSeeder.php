<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Workspace;
use App\Models\Project;
use App\Models\Task;
use App\Models\ProjectBaseline;
use App\Models\PlannedProgress;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Get users
        $john = User::where('email', 'john@example.com')->first();
        $jane = User::where('email', 'jane@example.com')->first();
        $bob = User::where('email', 'bob@example.com')->first();

        // Create Workspace
        $workspace = Workspace::create([
            'name' => 'IT Department',
            'description' => 'Workspace for IT projects',
            'created_by' => $john->id,
        ]);

        // Add members to workspace
        $workspace->members()->attach([
            $john->id => ['role' => 'admin'],
            $jane->id => ['role' => 'member'],
            $bob->id => ['role' => 'member'],
        ]);

        // Create Project
        $project = Project::create([
            'workspace_id' => $workspace->id,
            'name' => 'Website Redesign',
            'description' => 'Redesign company website with modern UI/UX',
            'start_date' => Carbon::now()->startOfMonth(),
            'end_date' => Carbon::now()->addMonths(3)->endOfMonth(),
            'status' => 'active',
            'created_by' => $john->id,
        ]);

        // Add members to project
        $project->members()->attach([
            $john->id => ['role' => 'manager'],
            $jane->id => ['role' => 'member'],
            $bob->id => ['role' => 'member'],
        ]);

        // Create Tasks
        $tasks = [
            [
                'name' => 'Design UI/UX',
                'description' => 'Create wireframes and mockups',
                'assignee_id' => $jane->id,
                'status' => 'completed',
                'priority' => 'high',
                'weight' => 3.0,
                'start_date' => Carbon::now()->startOfMonth(),
                'due_date' => Carbon::now()->startOfMonth()->addDays(14),
                'completed_at' => Carbon::now()->startOfMonth()->addDays(13),
            ],
            [
                'name' => 'Frontend Development',
                'description' => 'Implement responsive frontend',
                'assignee_id' => $jane->id,
                'status' => 'in_progress',
                'priority' => 'high',
                'weight' => 5.0,
                'start_date' => Carbon::now()->startOfMonth()->addDays(15),
                'due_date' => Carbon::now()->addMonths(1)->endOfMonth(),
            ],
            [
                'name' => 'Backend API Development',
                'description' => 'Build RESTful API',
                'assignee_id' => $bob->id,
                'status' => 'in_progress',
                'priority' => 'high',
                'weight' => 7.0,
                'start_date' => Carbon::now()->startOfMonth()->addDays(15),
                'due_date' => Carbon::now()->addMonths(2)->endOfMonth(),
            ],
            [
                'name' => 'Database Design',
                'description' => 'Design and create database schema',
                'assignee_id' => $bob->id,
                'status' => 'completed',
                'priority' => 'urgent',
                'weight' => 4.0,
                'start_date' => Carbon::now()->startOfMonth(),
                'due_date' => Carbon::now()->startOfMonth()->addDays(7),
                'completed_at' => Carbon::now()->startOfMonth()->addDays(6),
            ],
            [
                'name' => 'Testing & QA',
                'description' => 'Perform comprehensive testing',
                'assignee_id' => $jane->id,
                'status' => 'to_do',
                'priority' => 'medium',
                'weight' => 2.0,
                'start_date' => Carbon::now()->addMonths(2),
                'due_date' => Carbon::now()->addMonths(3)->subDays(7),
            ],
            [
                'name' => 'Deployment',
                'description' => 'Deploy to production server',
                'assignee_id' => $bob->id,
                'status' => 'to_do',
                'priority' => 'high',
                'weight' => 1.0,
                'start_date' => Carbon::now()->addMonths(3)->subDays(7),
                'due_date' => Carbon::now()->addMonths(3)->endOfMonth(),
            ],
        ];

        foreach ($tasks as $taskData) {
            Task::create(
                array_merge($taskData, [
                    'project_id' => $project->id,
                    'created_by' => $john->id,
                    'position' => 0,
                ]),
            );
        }

        // Create Baseline
        $baseline = ProjectBaseline::create([
            'project_id' => $project->id,
            'baseline_name' => 'Original Plan',
            'is_active' => true,
            'created_by' => $john->id,
        ]);

        // Create Planned Progress (kurva S)
        $startDate = Carbon::now()->startOfMonth();
        $endDate = Carbon::now()->addMonths(3)->endOfMonth();
        $totalDays = $startDate->diffInDays($endDate);

        $plannedProgressPoints = [
            0 => 0, // Start
            15 => 10, // 15 days
            30 => 25, // 1 month
            45 => 40, // 1.5 months
            60 => 60, // 2 months
            75 => 80, // 2.5 months
            90 => 95, // 3 months
            $totalDays => 100, // End
        ];

        foreach ($plannedProgressPoints as $days => $percentage) {
            PlannedProgress::create([
                'baseline_id' => $baseline->id,
                'date' => $startDate->copy()->addDays($days),
                'planned_cumulative_percentage' => $percentage,
            ]);
        }

        $this->command->info('✅ Test data created successfully!');
        $this->command->info('📊 Summary:');
        $this->command->info('   - 1 Workspace: IT Department');
        $this->command->info('   - 3 Members in workspace');
        $this->command->info('   - 1 Project: Website Redesign');
        $this->command->info('   - 6 Tasks created');
        $this->command->info('   - 1 Baseline with planned progress');
    }
}
