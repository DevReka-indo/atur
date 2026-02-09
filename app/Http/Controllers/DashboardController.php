<?php

namespace App\Http\Controllers;

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

        // Get recent tasks
        $recentTasks = $user->assignedTasks()
            ->with(['project', 'statusWeight'])
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
