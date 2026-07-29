<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\SsoCallbackController;
use App\Http\Controllers\Auth\SsoLoginController;
use App\Http\Controllers\Auth\SsoRedirectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DiscussionController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\PermissionManagementController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectTemplateCategoryController;
use App\Http\Controllers\ProjectTemplateController;
use App\Http\Controllers\ProjectTemplateGalleryController;
use App\Http\Controllers\ProjectTemplatePreviewController;
use App\Http\Controllers\ProjectTemplateTaskController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkspaceChatController;
use App\Http\Controllers\WorkspaceController;
use App\Http\Controllers\WorkspaceInvitationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Root
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }

    return view('auth.login');
})->name('home');

/*
|--------------------------------------------------------------------------
| Google Authentication
|--------------------------------------------------------------------------
*/

Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
    ->name('google.login');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
    ->name('google.callback');

/*
|--------------------------------------------------------------------------
| Single Sign-On
|--------------------------------------------------------------------------
*/

Route::get('/login/sso', SsoLoginController::class)
    ->middleware('guest')
    ->name('sso.login');

Route::get('/sso/redirect', SsoRedirectController::class)
    ->middleware('guest')
    ->name('sso.redirect');

Route::get('/sso/callback', SsoCallbackController::class)
    ->name('sso.callback');

/*
|--------------------------------------------------------------------------
| Public Invitations
|--------------------------------------------------------------------------
*/

Route::get('/invitations/accept/{token}', [InvitationController::class, 'accept'])
    ->name('invitations.accept');

Route::post('/invitations/store-session', [InvitationController::class, 'storeSession'])
    ->name('invitations.store-session');

Route::get('/join/{token}', [InvitationController::class, 'joinViaLink'])
    ->name('workspaces.invite.join');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Dashboard
    |--------------------------------------------------------------------------
    */

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/live-search', [DashboardController::class, 'live'])
        ->name('live.search');

    Route::get('/activity-log', [DashboardController::class, 'activityLog'])
        ->name('activity.log');

    Route::get('/overload', [DashboardController::class, 'overloadList'])
        ->name('overload.index');

    /*
    |--------------------------------------------------------------------------
    | Account Settings
    |--------------------------------------------------------------------------
    */

    Route::get('/settings/account', [DashboardController::class, 'account'])
        ->name('settings.account');

    Route::get('/settings/about', [DashboardController::class, 'about'])
        ->name('settings.about');

    Route::post('/switch-account/{id}', [DashboardController::class, 'switchAccount'])
        ->name('switch.account');

    Route::delete('/settings/account/remove/{id}', [DashboardController::class, 'removeAccountFromDevice'])
        ->name('account.remove.device');

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    Route::get('/settings/notifications', [DashboardController::class, 'notifications'])
        ->name('notifications.index');

    Route::get('/notifications/poll', [DashboardController::class, 'poll'])
        ->name('notifications.poll');

    Route::post('/notifications/read-all', [DashboardController::class, 'markAllAsRead'])
        ->name('notifications.readAll');

    Route::post('/notifications/{id}/read', [DashboardController::class, 'markAsRead'])
        ->name('notifications.read');

    Route::get('/notifications/{id}/open', [DashboardController::class, 'openNotification'])
        ->name('notifications.open');

    Route::delete('/notifications/{id}', [DashboardController::class, 'destroy'])
        ->name('notifications.destroy');

    /*
    |--------------------------------------------------------------------------
    | Workspaces
    |--------------------------------------------------------------------------
    */

    Route::get('/workspaces', [WorkspaceController::class, 'index'])
        ->name('workspaces.index');

    Route::get('/workspaces/create', [WorkspaceController::class, 'create'])
        ->name('workspaces.create');

    Route::post('/workspaces', [WorkspaceController::class, 'store'])
        ->name('workspaces.store');

    Route::get('/workspaces/{workspace:token}/chat/messages', [WorkspaceChatController::class, 'index'])
        ->middleware('throttle:workspace-chat-poll')
        ->name('workspace-chat.messages.index');

    Route::post('/workspaces/{workspace:token}/chat/messages', [WorkspaceChatController::class, 'store'])
        ->middleware('throttle:workspace-chat-write')
        ->name('workspace-chat.messages.store');

    Route::patch('/workspaces/{workspace:token}/chat/messages/{message}', [WorkspaceChatController::class, 'update'])
        ->middleware('throttle:workspace-chat-write')
        ->name('workspace-chat.messages.update');

    Route::delete('/workspaces/{workspace:token}/chat/messages/{message}', [WorkspaceChatController::class, 'destroy'])
        ->middleware('throttle:workspace-chat-write')
        ->name('workspace-chat.messages.destroy');

    Route::post('/workspaces/{workspace:token}/chat/read', [WorkspaceChatController::class, 'markRead'])
        ->middleware('throttle:workspace-chat-poll')
        ->name('workspace-chat.read');

    Route::get('/workspaces/{workspace:token}/chat/mentions', [WorkspaceChatController::class, 'mentionCandidates'])
        ->middleware('throttle:workspace-chat-mentions')
        ->name('workspace-chat.mentions');

    Route::get('/workspaces/{token}', [WorkspaceController::class, 'show'])
        ->name('workspaces.show');

    Route::get('/workspaces/{token}/edit', [WorkspaceController::class, 'edit'])
        ->name('workspaces.edit');

    Route::put('/workspaces/{token}', [WorkspaceController::class, 'update'])
        ->name('workspaces.update');

    Route::patch('/workspaces/{token}', [WorkspaceController::class, 'update'])
        ->name('workspaces.patch-update');

    Route::delete('/workspaces/{token}', [WorkspaceController::class, 'destroy'])
        ->name('workspaces.destroy');

    /*
    |--------------------------------------------------------------------------
    | Workspace Members
    |--------------------------------------------------------------------------
    */

    Route::post('/workspaces/{token}/members', [WorkspaceController::class, 'addMember'])
        ->name('workspaces.members.store');

    Route::get('/workspaces/{token}/member-candidates', [WorkspaceInvitationController::class, 'candidates'])
        ->middleware('throttle:workspace-member-search')
        ->name('workspaces.members.candidates');

    Route::post('/workspaces/{token}/invitations', [WorkspaceInvitationController::class, 'store'])
        ->middleware('throttle:workspace-invitations')
        ->name('workspaces.invitations.store');

    Route::post('/workspaces/{token}/invitations/{invitation}/resend', [WorkspaceInvitationController::class, 'resend'])
        ->middleware('throttle:workspace-invitation-resend')
        ->name('workspaces.invitations.resend');

    Route::delete('/workspaces/{token}/invitations/{invitation}', [WorkspaceInvitationController::class, 'revoke'])
        ->name('workspaces.invitations.revoke');

    Route::patch('/workspaces/{token}/members/{user}', [WorkspaceController::class, 'updateMemberRole'])
        ->name('workspaces.members.update');

    Route::delete('/workspaces/{token}/members/{user}', [WorkspaceController::class, 'removeMember'])
        ->name('workspaces.members.destroy');

    Route::delete('/workspaces/{token}/members/{user}/cascade', [WorkspaceController::class, 'removeMemberCascade'])
        ->name('workspaces.members.destroy.cascade');

    /*
    |--------------------------------------------------------------------------
    | Workspace Invite Links
    |--------------------------------------------------------------------------
    */

    Route::post('/workspaces/{token}/invite-link/generate', [WorkspaceController::class, 'generateInviteLink'])
        ->name('workspaces.invite.generate');

    Route::post('/workspaces/{token}/invite-link/reset', [WorkspaceController::class, 'resetInviteLink'])
        ->name('workspaces.invite.reset');

    Route::delete('/workspaces/{token}/invite-link', [WorkspaceController::class, 'revokeInviteLink'])
        ->name('workspaces.invite.revoke');

    Route::post('/workspaces/{token}/invite-link/accept', [InvitationController::class, 'acceptViaLink'])
        ->name('workspaces.invite.accept');

    Route::post('/workspaces/{token}/invite-link/decline', [InvitationController::class, 'declineViaLink'])
        ->name('workspaces.invite.decline');

    /*
    |--------------------------------------------------------------------------
    | Projects
    |--------------------------------------------------------------------------
    */

    Route::get('/projects', [ProjectController::class, 'index'])
        ->name('projects.index');

    Route::get('/projects/create', [ProjectController::class, 'create'])
        ->name('projects.create');

    Route::post('/projects', [ProjectController::class, 'store'])
        ->name('projects.store');

    Route::get('/template-gallery', [ProjectTemplateGalleryController::class, 'index'])
        ->name('template-gallery.index');

    Route::get('/template-gallery/{projectTemplate:slug}', [ProjectTemplateGalleryController::class, 'show'])
        ->name('template-gallery.show');

    Route::get('/projects/{token}', [ProjectController::class, 'show'])
        ->name('projects.show');

    Route::get('/projects/{token}/edit', [ProjectController::class, 'edit'])
        ->name('projects.edit');

    Route::put('/projects/{token}', [ProjectController::class, 'update'])
        ->name('projects.update');

    Route::delete('/projects/{token}', [ProjectController::class, 'destroy'])
        ->name('projects.destroy');

    Route::patch('/projects/{token}/status', [ProjectController::class, 'updateStatus'])
        ->name('projects.updateStatus');

    /*
    |--------------------------------------------------------------------------
    | Project Members
    |--------------------------------------------------------------------------
    */

    Route::post('/projects/{token}/members', [ProjectController::class, 'addMember'])
        ->name('projects.members.store');

    Route::patch('/projects/{token}/members/{user}', [ProjectController::class, 'updateMemberRole'])
        ->name('projects.members.update');

    Route::delete('/projects/{token}/members/{user}', [ProjectController::class, 'removeMember'])
        ->name('projects.members.destroy');

    /*
    |--------------------------------------------------------------------------
    | Project Data
    |--------------------------------------------------------------------------
    */

    Route::get('/projects/{id}/tasks-json', [TaskController::class, 'tasksJson'])
        ->name('projects.tasks.json');

    Route::get('/projects/{id}/assignees-json', [TaskController::class, 'assigneesJson'])
        ->name('projects.assignees.json');

    Route::get('/gantt/project-data', [ProjectController::class, 'ganttData'])
        ->name('gantt.project.data');

    /*
    |--------------------------------------------------------------------------
    | Tasks
    |--------------------------------------------------------------------------
    */

    Route::get('/tasks', [TaskController::class, 'index'])
        ->name('tasks.index');

    Route::get('/tasks/create', [TaskController::class, 'create'])
        ->name('tasks.create');

    Route::post('/tasks', [TaskController::class, 'store'])
        ->name('tasks.store');

    Route::get('/tasks/{token}', [TaskController::class, 'show'])
        ->name('tasks.show');

    Route::get('/tasks/{token}/edit', [TaskController::class, 'edit'])
        ->name('tasks.edit');

    Route::put('/tasks/{token}', [TaskController::class, 'update'])
        ->name('tasks.update');

    Route::patch('/tasks/{token}', [TaskController::class, 'update'])
        ->name('tasks.patch-update');

    Route::delete('/tasks/{token}', [TaskController::class, 'destroy'])
        ->name('tasks.destroy');

    Route::patch('/tasks/{token}/status', [TaskController::class, 'updateStatus'])
        ->name('tasks.updateStatus');

    Route::post('/tasks/{token}/mark-seen', [TaskController::class, 'markSeen'])
        ->name('tasks.markSeen');

    /*
    |--------------------------------------------------------------------------
    | Task Comments and Attachments
    |--------------------------------------------------------------------------
    */

    Route::post('/tasks/{token}/comments', [TaskController::class, 'storeComment'])
        ->name('tasks.comments.store');

    Route::post('/tasks/{token}/attachments', [TaskController::class, 'storeAttachment'])
        ->name('tasks.attachments.store');

    Route::get('/tasks/{token}/attachments/{attachment}/download', [TaskController::class, 'downloadAttachment'])
        ->name('tasks.attachments.download');

    /*
    |--------------------------------------------------------------------------
    | Task Gantt Data
    |--------------------------------------------------------------------------
    */

    Route::get('/gantt/data', [TaskController::class, 'ganttData'])
        ->name('gant.data');

    /*
    |--------------------------------------------------------------------------
    | Management Users
    |--------------------------------------------------------------------------
    */

    Route::get('/management-users', [UserController::class, 'index'])
        ->middleware('permission:management-users.view')
        ->name('management-users.index');

    Route::get('/management-users/create', [UserController::class, 'create'])
        ->middleware('permission:management-users.create')
        ->name('management-users.create');

    Route::post('/management-users', [UserController::class, 'store'])
        ->middleware('permission:management-users.create')
        ->name('management-users.store');

    Route::get('/management-users/{management_user}', [UserController::class, 'show'])
        ->middleware('permission:management-users.view')
        ->name('management-users.show');

    Route::get('/management-users/{management_user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:management-users.update')
        ->name('management-users.edit');

    Route::put('/management-users/{management_user}', [UserController::class, 'update'])
        ->middleware('permission:management-users.update')
        ->name('management-users.update');

    Route::delete('/management-users/{management_user}', [UserController::class, 'destroy'])
        ->middleware('permission:management-users.delete')
        ->name('management-users.destroy');

    Route::patch('/management-users/{management_user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->middleware('permission:management-users.toggle-status')
        ->name('management-users.toggle-status');

    /*
    |--------------------------------------------------------------------------
    | Management Projects
    |--------------------------------------------------------------------------
    */

    Route::get('/management-projects', [ProjectController::class, 'managementIndex'])
        ->middleware('permission:management-projects.view')
        ->name('managementprojects.index');

    Route::delete('/management-projects/{token}', [ProjectController::class, 'managementDestroy'])
        ->middleware('permission:management-projects.delete')
        ->name('managementprojects.destroy');

    /*
    |--------------------------------------------------------------------------
    | Management Workspaces
    |--------------------------------------------------------------------------
    */

    Route::get('/management-workspaces', [WorkspaceController::class, 'managementIndex'])
        ->middleware('permission:management-workspaces.view')
        ->name('managementworkspaces.index');

    Route::delete('/management-workspaces/{token}', [WorkspaceController::class, 'managementDestroy'])
        ->middleware('permission:management-workspaces.delete')
        ->name('managementworkspaces.destroy');

    /*
    |--------------------------------------------------------------------------
    | Management Roles
    |--------------------------------------------------------------------------
    */

    Route::get('/management-roles', [RolePermissionController::class, 'index'])
        ->middleware('permission:roles.view')
        ->name('management-roles.index');

    Route::get('/management-roles/create', [RolePermissionController::class, 'create'])
        ->middleware('permission:roles.create')
        ->name('management-roles.create');

    Route::post('/management-roles', [RolePermissionController::class, 'store'])
        ->middleware('permission:roles.create')
        ->name('management-roles.store');

    Route::get('/management-roles/{role}/edit', [RolePermissionController::class, 'edit'])
        ->middleware('permission:roles.view')
        ->name('management-roles.edit');

    Route::put('/management-roles/{role}', [RolePermissionController::class, 'update'])
        ->middleware('permission:roles.update')
        ->name('management-roles.update');

    /*
    |--------------------------------------------------------------------------
    | Management Permissions
    |--------------------------------------------------------------------------
    */

    Route::get('/management-permissions', [PermissionManagementController::class, 'index'])
        ->middleware('permission:permissions.view')
        ->name('management-permissions.index');

    Route::get('/management-permissions/create', [PermissionManagementController::class, 'create'])
        ->middleware('permission:permissions.create')
        ->name('management-permissions.create');

    Route::post('/management-permissions', [PermissionManagementController::class, 'store'])
        ->middleware('permission:permissions.create')
        ->name('management-permissions.store');

    /*
    |--------------------------------------------------------------------------
    | Project Template Categories
    |--------------------------------------------------------------------------
    */

    Route::get('/project-template-categories', [ProjectTemplateCategoryController::class, 'index'])
        ->middleware('permission:project-template-categories.view')
        ->name('project-template-categories.index');

    Route::get('/project-template-categories/create', [ProjectTemplateCategoryController::class, 'create'])
        ->middleware('permission:project-template-categories.create')
        ->name('project-template-categories.create');

    Route::post('/project-template-categories', [ProjectTemplateCategoryController::class, 'store'])
        ->middleware('permission:project-template-categories.create')
        ->name('project-template-categories.store');

    Route::get('/project-template-categories/{project_template_category:slug}/edit', [ProjectTemplateCategoryController::class, 'edit'])
        ->middleware('permission:project-template-categories.update')
        ->name('project-template-categories.edit');

    Route::put('/project-template-categories/{project_template_category:slug}', [ProjectTemplateCategoryController::class, 'update'])
        ->middleware('permission:project-template-categories.update')
        ->name('project-template-categories.update');

    Route::patch('/project-template-categories/{project_template_category:slug}/status', [ProjectTemplateCategoryController::class, 'toggleStatus'])
        ->middleware('permission:project-template-categories.update')
        ->name('project-template-categories.toggle-status');

    Route::delete('/project-template-categories/{project_template_category:slug}', [ProjectTemplateCategoryController::class, 'destroy'])
        ->middleware('permission:project-template-categories.delete')
        ->name('project-template-categories.destroy');

    /*
    |--------------------------------------------------------------------------
    | Project Templates
    |--------------------------------------------------------------------------
    */

    Route::get('/project-templates', [ProjectTemplateController::class, 'index'])
        ->middleware('permission:project-templates.view')
        ->name('project-templates.index');

    Route::get('/project-templates/create', [ProjectTemplateController::class, 'create'])
        ->middleware('permission:project-templates.create')
        ->name('project-templates.create');

    Route::post('/project-templates', [ProjectTemplateController::class, 'store'])
        ->middleware('permission:project-templates.create')
        ->name('project-templates.store');

    Route::get('/project-templates/{projectTemplate:id}/preview', ProjectTemplatePreviewController::class)
        ->name('project-templates.preview');

    Route::get('/project-templates/{project_template:slug}', [ProjectTemplateController::class, 'show'])
        ->middleware('permission:project-templates.view')
        ->name('project-templates.show');

    Route::get('/project-templates/{project_template:slug}/edit', [ProjectTemplateController::class, 'edit'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.edit');

    Route::put('/project-templates/{project_template:slug}', [ProjectTemplateController::class, 'update'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.update');

    Route::patch('/project-templates/{project_template:slug}/status', [ProjectTemplateController::class, 'toggleStatus'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.toggle-status');

    Route::delete('/project-templates/{project_template:slug}', [ProjectTemplateController::class, 'destroy'])
        ->middleware('permission:project-templates.delete')
        ->name('project-templates.destroy');

    Route::post('/project-templates/{project_template:slug}/tasks', [ProjectTemplateTaskController::class, 'store'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.tasks.store');

    Route::patch('/project-templates/{project_template:slug}/tasks/reorder', [ProjectTemplateTaskController::class, 'reorder'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.tasks.reorder');

    Route::put('/project-templates/{project_template:slug}/tasks/{template_task}', [ProjectTemplateTaskController::class, 'update'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.tasks.update');

    Route::delete('/project-templates/{project_template:slug}/tasks/{template_task}', [ProjectTemplateTaskController::class, 'destroy'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.tasks.destroy');

    Route::put('/project-templates/{project_template:slug}/tasks/{template_task}/dependency', [ProjectTemplateTaskController::class, 'updateDependency'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.tasks.dependency.update');

    Route::delete('/project-templates/{project_template:slug}/tasks/{template_task}/dependency', [ProjectTemplateTaskController::class, 'destroyDependency'])
        ->middleware('permission:project-templates.update')
        ->name('project-templates.tasks.dependency.destroy');

    /*
    |--------------------------------------------------------------------------
    | Discussion
    |--------------------------------------------------------------------------
    */

    Route::get('/discussion', [DiscussionController::class, 'index'])
        ->name('discussion.index');

    /*
     * Route statis harus berada sebelum route /discussion/{project}.
     */
    Route::get('/discussion/unread-sidebar', [DiscussionController::class, 'unreadSidebar'])
        ->name('discussion.unread-sidebar');

    Route::get('/discussion/{project}/unread', [DiscussionController::class, 'unreadCounts'])
        ->name('discussion.unread');

    Route::get('/discussion/{project}/unread-counts', [DiscussionController::class, 'unreadCounts'])
        ->name('discussion.unread-counts');

    Route::post('/discussion/{project}/threads', [DiscussionController::class, 'storeThread'])
        ->name('discussion.threads.store');

    Route::patch('/discussion/{project}/threads/{thread}', [DiscussionController::class, 'updateThread'])
        ->name('discussion.threads.update')
        ->scopeBindings();

    Route::delete('/discussion/{project}/threads/{thread}', [DiscussionController::class, 'destroyThread'])
        ->name('discussion.threads.destroy')
        ->scopeBindings();

    Route::get('/discussion/{project}/{thread}', [DiscussionController::class, 'chat'])
        ->name('discussion.chat')
        ->scopeBindings();

    Route::get('/discussion/{project}/{thread}/messages', [DiscussionController::class, 'messages'])
        ->middleware('throttle:project-discussion-poll')
        ->name('discussion.messages.index')
        ->scopeBindings();

    Route::post('/discussion/{project}/{thread}/read', [DiscussionController::class, 'markThreadRead'])
        ->middleware('throttle:project-discussion-poll')
        ->name('discussion.messages.read')
        ->scopeBindings();

    Route::get('/discussion/{project}/{thread}/mention-candidates', [DiscussionController::class, 'mentionCandidates'])
        ->middleware('throttle:project-discussion-mentions')
        ->name('discussion.mention-candidates')
        ->scopeBindings();

    /*
     * Endpoint canonical penyimpanan pesan.
     * Sebelumnya belum memiliki nama route.
     */
    Route::post('/discussion/{project}/{thread}/messages', [DiscussionController::class, 'storeMessage'])
        ->middleware('throttle:project-discussion-write')
        ->name('discussion.messages.store')
        ->scopeBindings();

    /*
     * Endpoint legacy tetap dipertahankan tanpa mengambil nama route canonical.
     */
    Route::post('/discussion/{project}/thread/{thread}/messages', [DiscussionController::class, 'storeMessage'])
        ->middleware('throttle:project-discussion-write')
        ->scopeBindings();

    Route::patch('/discussion/{project}/thread/{thread}/messages/{message}', [DiscussionController::class, 'updateMessage'])
        ->middleware('throttle:project-discussion-write')
        ->name('messages.update')
        ->scopeBindings();

    Route::delete('/discussion/{project}/thread/{thread}/messages/{message}', [DiscussionController::class, 'destroyMessage'])
        ->middleware('throttle:project-discussion-write')
        ->name('messages.destroy')
        ->scopeBindings();

    /*
     * Route dinamis project diletakkan paling bawah.
     */
    Route::get('/discussion/{project}', [DiscussionController::class, 'show'])
        ->name('discussion.show');

    /*
    |--------------------------------------------------------------------------
    | Profile
    |--------------------------------------------------------------------------
    */

    Route::get('/profile', [ProfileController::class, 'edit'])
        ->name('profile.edit');

    Route::patch('/profile', [ProfileController::class, 'update'])
        ->name('profile.update');

    Route::delete('/profile', [ProfileController::class, 'destroy'])
        ->name('profile.destroy');

    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
        ->name('profile.photo.update');

    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])
        ->name('profile.photo.delete');

    /*
    |--------------------------------------------------------------------------
    | Invitations
    |--------------------------------------------------------------------------
    */

    Route::post('/invitations/send', [InvitationController::class, 'send'])
        ->name('invitations.send');

    Route::post('/invitations/join', [InvitationController::class, 'join'])
        ->name('invitations.join');

    Route::post('/invitations/reject', [InvitationController::class, 'reject'])
        ->name('invitations.reject');

    Route::post('/invitations/decline', [InvitationController::class, 'decline'])
        ->name('invitations.decline');
});

/*
|--------------------------------------------------------------------------
| Breeze Authentication Routes
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';
