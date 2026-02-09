<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get statistics
        $stats = [
            'total_workspaces' => $user->workspaces()->count(),
            'total_projects' => $user->projects()->count(),
            'assigned_tasks' => $user->assignedTasks()->count(),
            'completed_tasks' => $user->assignedTasks()->where('status', 'completed')->count(),
        ];

        // Get recent tasks related to user projects/ownership/assignment
        $projectIds = $user->projects()->pluck('projects.id');

        $recentTasks = Task::query()
            ->with(['project', 'statusWeight'])
            ->where(function ($query) use ($user, $projectIds) {
                $query->where('assignee_id', $user->id)
                    ->orWhere('created_by', $user->id)
                    ->orWhereIn('project_id', $projectIds);
            })
            ->latest()
            ->take(5)
            ->get();

        // Get active projects
        $activeProjects = $user->projects()
            ->where('status', 'active')
            ->with(['workspace'])
            ->withCount('tasks')
            ->take(5)
            ->get();

        return view('dashboard.index', compact('stats', 'recentTasks', 'activeProjects'));
    }
}
