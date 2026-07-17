<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\Auth\SsoCallbackController;
use App\Http\Controllers\Auth\SsoLoginController;
use App\Http\Controllers\Auth\SsoRedirectController;
use Illuminate\Support\Facades\Auth;

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])->name('google.login');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('google.callback');

// SSO
Route::middleware('guest')->group(function () {
    Route::get('/login/sso', SsoLoginController::class)->name('sso.login');
    Route::get('/sso/redirect', SsoRedirectController::class)->name('sso.redirect');
});
Route::get('/sso/callback', SsoCallbackController::class)->name('sso.callback');

// Welcome
// Route::get('/', fn() => view('welcome'));
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('auth.login');
});

// Invitation
Route::get('/invitations/accept/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
Route::post('/invitations/store-session', [InvitationController::class, 'storeSession'])->name('invitations.store-session');
Route::get('/join/{token}', [InvitationController::class, 'joinViaLink'])->name('workspaces.invite.join');

Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/live-search', [DashboardController::class, 'live'])->name('live.search');
    Route::post('/switch-account/{id}', [DashboardController::class, 'switchAccount'])->name('switch.account');
    Route::delete('/settings/account/remove/{id}', [DashboardController::class, 'removeAccountFromDevice'])->name('account.remove.device');
    Route::get('/settings/account', [DashboardController::class, 'account'])->name('settings.account');
    Route::get('/settings/about', [DashboardController::class, 'about'])->name('settings.about');

    //notif
    Route::get('/settings/notifications', [DashboardController::class, 'notifications'])->name('notifications.index');
    Route::get('/notifications/poll', [DashboardController::class, 'poll'])->name('notifications.poll');
    Route::post('/notifications/read-all', [DashboardController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::post('/notifications/{id}/read', [DashboardController::class, 'markAsRead'])->name('notifications.read');
    Route::delete('/notifications/{id}', [DashboardController::class, 'destroy'])->name('notifications.destroy');

    //activity log
    Route::get('/activity-log', [DashboardController::class, 'activityLog'])->name('activity.log');

    //overload
    Route::get('/overload', [DashboardController::class, 'overloadList'])->name('overload.index');

    // Workspaces
    Route::get('/workspaces', [WorkspaceController::class, 'index'])->name('workspaces.index');

    // Route::resource('workspaces.issues', WorkspaceController::class);
    Route::get('/workspaces/create', [WorkspaceController::class, 'create'])->name('workspaces.create');
    Route::post('/workspaces', [WorkspaceController::class, 'store'])->name('workspaces.store');
    Route::get('/workspaces/{token}', [WorkspaceController::class, 'show'])->name('workspaces.show');
    Route::get('/workspaces/{token}/edit', [WorkspaceController::class, 'edit'])->name('workspaces.edit');
    Route::put('/workspaces/{token}', [WorkspaceController::class, 'update'])->name('workspaces.update');
    Route::patch('/workspaces/{token}', [WorkspaceController::class, 'update']);
    Route::delete('/workspaces/{token}', [WorkspaceController::class, 'destroy'])->name('workspaces.destroy');
    Route::delete('/workspaces/{token}/members/{user}/cascade', [WorkspaceController::class, 'removeMemberCascade'])->name('workspaces.members.destroy.cascade');

    // Workspace members
    Route::post('/workspaces/{token}/members', [WorkspaceController::class, 'addMember'])->name('workspaces.members.store');
    Route::patch('/workspaces/{token}/members/{user}', [WorkspaceController::class, 'updateMemberRole'])->name('workspaces.members.update');
    Route::delete('/workspaces/{token}/members/{user}', [WorkspaceController::class, 'removeMember'])->name('workspaces.members.destroy');

    // Workspace invite link
    Route::post('/workspaces/{token}/invite-link/generate', [WorkspaceController::class, 'generateInviteLink'])->name('workspaces.invite.generate');
    Route::post('/workspaces/{token}/invite-link/reset', [WorkspaceController::class, 'resetInviteLink'])->name('workspaces.invite.reset');
    Route::post('/workspaces/{token}/invite-link/accept', [InvitationController::class, 'acceptViaLink'])->name('workspaces.invite.accept');
    Route::post('/workspaces/{token}/invite-link/decline', [InvitationController::class, 'declineViaLink'])->name('workspaces.invite.decline');

    // Projects
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{token}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('/projects/{token}/edit', [ProjectController::class, 'edit'])->name('projects.edit');
    Route::put('/projects/{token}', [ProjectController::class, 'update'])->name('projects.update');

    // Route::patch('/projects/{token}', [ProjectController::class, 'update']);
    Route::delete('/projects/{token}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/gantt/project-data', [ProjectController::class, 'ganttData'])->name('gantt.project.data');

    // Project members
    Route::post('/projects/{token}/members', [ProjectController::class, 'addMember'])->name('projects.members.store');
    Route::patch('/projects/{token}/members/{user}', [ProjectController::class, 'updateMemberRole'])->name('projects.members.update');
    Route::delete('/projects/{token}/members/{user}', [ProjectController::class, 'removeMember'])->name('projects.members.destroy');
    Route::patch('/projects/{token}/status', [ProjectController::class, 'updateStatus'])->name('projects.updateStatus');

    // Tasks
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::get('/tasks/{token}', [TaskController::class, 'show'])->name('tasks.show');
    Route::get('/tasks/{token}/edit', [TaskController::class, 'edit'])->name('tasks.edit');
    Route::put('/tasks/{token}', [TaskController::class, 'update'])->name('tasks.update');
    Route::patch('/tasks/{token}', [TaskController::class, 'update']);
    Route::delete('/tasks/{token}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::get('/projects/{id}/tasks-json', [TaskController::class, 'tasksJson'])->name('projects.tasks.json');
    // Task action
    Route::post('/tasks/{token}/comments', [TaskController::class, 'storeComment'])->name('tasks.comments.store');
    Route::post('/tasks/{token}/attachments', [TaskController::class, 'storeAttachment'])->name('tasks.attachments.store');
    Route::get('/tasks/{token}/attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])->name('tasks.attachments.download');
    Route::patch('/tasks/{token}/status', [TaskController::class, 'updateStatus'])->name('tasks.updateStatus');
    Route::get('/gantt/data', [TaskController::class, 'ganttData'])->name('gant.data');
    Route::get('/projects/{id}/assignees-json', [TaskController::class, 'assigneesJson']);
    Route::post('/tasks/{token}/mark-seen', [TaskController::class, 'markSeen'])->name('tasks.markSeen');

    // Management
    Route::resource('management-users', UserController::class);
    Route::patch('management-users/{management_user}/toggle-status', [UserController::class, 'toggleStatus'])->name('management-users.toggle-status');
    Route::get('/management-projects', [ProjectController::class, 'managementIndex'])->name('managementprojects.index');
    Route::get('/management-workspaces', [WorkspaceController::class, 'managementIndex'])->name('managementworkspaces.index');
    Route::delete('/management-workspaces/{token}', [WorkspaceController::class, 'managementDestroy'])->name('managementworkspaces.destroy');

    // Discussion
    Route::get('/discussion', [DiscussionController::class, 'index'])->name('discussion.index');
    Route::get('/discussion/unread-sidebar', [DiscussionController::class, 'unreadSidebar'])->name('discussion.unread-sidebar');
    Route::get('/discussion/{project}/unread', [DiscussionController::class, 'unreadCounts'])->name('discussion.unread');
    Route::get('/discussion/{project}/unread-counts', [DiscussionController::class, 'unreadCounts'])->name('discussion.unread-counts');
    Route::get('/discussion/{project}', [DiscussionController::class, 'show'])->name('discussion.show');
    Route::post('/discussion/{project}/threads', [DiscussionController::class, 'storeThread'])->name('discussion.threads.store');
    Route::patch('/discussion/{project}/threads/{thread}', [DiscussionController::class, 'updateThread'])->name('discussion.threads.update');
    Route::delete('/discussion/{project}/threads/{thread}', [DiscussionController::class, 'destroyThread'])->name('discussion.threads.destroy');
    Route::get('/discussion/{project}/{thread}', [DiscussionController::class, 'chat'])->name('discussion.chat');
    Route::post('/discussion/{project}/{thread}/messages', [DiscussionController::class, 'storeMessage']);
    Route::post('/discussion/{project}/thread/{thread}/messages', [DiscussionController::class, 'storeMessage'])->name('messages.store');
    Route::patch('/discussion/{project}/thread/{thread}/messages/{message}', [DiscussionController::class, 'updateMessage'])->name('messages.update');
    Route::delete('/discussion/{project}/thread/{thread}/messages/{message}', [DiscussionController::class, 'destroyMessage'])->name('messages.destroy');

    // Profile
    Route::prefix('profile')
        ->name('profile.')
        ->group(function () {
            Route::get('/', [ProfileController::class, 'edit'])->name('edit');
            Route::patch('/', [ProfileController::class, 'update'])->name('update');
            Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
            Route::post('/photo', [ProfileController::class, 'updatePhoto'])->name('photo.update');
            Route::delete('/photo', [ProfileController::class, 'deletePhoto'])->name('photo.delete');
        });

    // Invitations
    Route::post('/invitations/send', [InvitationController::class, 'send'])->name('invitations.send');
    Route::post('/invitations/join', [InvitationController::class, 'join'])->name('invitations.join');
    Route::post('/invitations/reject', [InvitationController::class, 'reject'])->name('invitations.reject');
    Route::post('/invitations/decline', [InvitationController::class, 'decline'])->name('invitations.decline');
});

require __DIR__ . '/auth.php';
