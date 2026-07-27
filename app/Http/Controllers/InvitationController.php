<?php

namespace App\Http\Controllers;

use App\Mail\InvitationMail;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function __construct(
        private readonly WorkspaceInvitationService $workspaceInvitationService,
    ) {}

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email:rfc', 'max:255'],
            'type' => ['required', 'in:workspace,project'],
            'invitable_id' => ['required', 'integer'],
        ]);

        if ($validated['type'] === 'workspace') {
            $workspace = Workspace::query()->findOrFail($validated['invitable_id']);
            abort_unless($workspace->canManageMembers($request->user()), 403);

            $result = $this->workspaceInvitationService->invite(
                $workspace,
                $request->user(),
                Workspace::ROLE_MEMBER,
                null,
                $validated['email'],
            );

            return back()->with('invite_success', $result['message']);
        }

        $project = Project::query()->findOrFail($validated['invitable_id']);
        $plainTextToken = (string) Str::uuid();

        $invitation = Invitation::create([
            'email' => strtolower(trim($validated['email'])),
            'token' => Invitation::hashToken($plainTextToken),
            'type' => 'project',
            'invitable_id' => $project->id,
            'invited_by' => $request->user()->id,
            'role' => 'member',
            'status' => Invitation::STATUS_PENDING,
            'expires_at' => now()->addDays(3),
            'last_sent_at' => now(),
        ])->load('inviter');

        Mail::to($invitation->email)->queue(new InvitationMail(
            $invitation,
            $project->name,
            $plainTextToken,
            'Project Member',
        ));

        return back()->with('invite_success', 'Invitation sent to '.$invitation->email);
    }

    public function accept(string $token): View|RedirectResponse
    {
        $invitation = Invitation::findByPlainTextToken($token);

        if (! $invitation?->isUsable()) {
            return redirect()->route('login')
                ->with('error', 'Invitation is invalid, expired, or has been revoked.');
        }

        if (auth()->check() && strcasecmp(auth()->user()->email, $invitation->email) !== 0) {
            abort(403, 'Gunakan akun dengan email yang sama dengan undangan.');
        }

        $invitable = $invitation->type === 'workspace'
            ? Workspace::query()->find($invitation->invitable_id)
            : Project::query()->find($invitation->invitable_id);
        abort_if(! $invitable, 404);

        $invitation->load('inviter');
        $invitedUser = User::query()
            ->select(['id', 'name', 'email', 'profile_photo'])
            ->whereRaw('LOWER(email) = ?', [strtolower($invitation->email)])
            ->first();

        return view('invitations.index', compact(
            'invitation',
            'invitable',
            'invitedUser',
            'token',
        ));
    }

    public function complete(): void
    {
        if (session()->has('invitation_token') && auth()->check()) {
            $this->acceptPendingInvitation(session('invitation_token'), auth()->user());
        }
    }

    public function join(Request $request): RedirectResponse
    {
        $token = (string) session('invitation_token', $request->input('token', ''));

        if ($token === '') {
            return redirect()->route('dashboard');
        }

        $this->acceptPendingInvitation($token, $request->user());
        $request->session()->forget('invitation_token');

        return redirect()->route('dashboard')->with('success', 'You have successfully joined!');
    }

    public function reject(Request $request): RedirectResponse
    {
        $request->session()->forget('invitation_token');

        return redirect()->route('dashboard')->with('info', 'Invitation rejected.');
    }

    public function storeSession(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
            'redirect' => ['nullable', 'in:login,register'],
        ]);
        $invitation = Invitation::findByPlainTextToken($validated['token']);

        if (! $invitation?->isUsable()) {
            return redirect()->route('login')
                ->with('error', 'Invitation is invalid, expired, or has been revoked.');
        }

        $request->session()->put('invitation_token', $validated['token']);

        if ($request->user()) {
            return redirect()->route('invitations.accept', $validated['token']);
        }

        return redirect()->route($validated['redirect'] ?? 'login');
    }

    public function decline(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'max:255'],
        ]);
        $invitation = Invitation::findByPlainTextToken($validated['token']);

        if ($invitation?->isUsable()) {
            $invitation->update([
                'revoked_at' => now(),
                'pending_key' => null,
            ]);
        }

        $request->session()->forget('invitation_token');

        return redirect()->route('login')->with('info', 'Invitation declined.');
    }

    public function joinViaLink(string $token): View|RedirectResponse
    {
        $workspace = Workspace::query()->where('invite_token', $token)->firstOrFail();

        if (! $workspace->hasActiveInviteLink()) {
            return redirect()->route('login')
                ->with('error', 'Invite link sudah kedaluwarsa atau dinonaktifkan.');
        }

        if (! auth()->check()) {
            session(['workspace_invite_token' => $token]);

            return redirect()->route('login', ['invite_token' => $token])
                ->with('info', 'Silakan login terlebih dahulu untuk bergabung.');
        }

        $user = auth()->user();

        if ($workspace->isMember($user) || $workspace->isOwner($user)) {
            return redirect()->route('workspaces.show', $workspace->token)
                ->with('info', 'Kamu sudah menjadi member workspace ini.');
        }

        return view('invitations.confirm', compact('workspace', 'token'));
    }

    public function acceptViaLink(Request $request, string $token): RedirectResponse
    {
        $workspace = DB::transaction(function () use ($request, $token): Workspace {
            $workspace = Workspace::query()->where('token', $token)->lockForUpdate()->firstOrFail();

            if (! hash_equals((string) $workspace->invite_token, (string) $request->input('token'))
                || ! $workspace->hasActiveInviteLink()) {
                abort(403, 'Invalid or expired invite token.');
            }

            if (! $workspace->isMember($request->user()) && ! $workspace->isOwner($request->user())) {
                $workspace->members()->attach($request->user()->id, [
                    'role' => Workspace::ROLE_MEMBER,
                    'status' => 'active',
                    'joined_at' => now(),
                ]);
            }

            return $workspace;
        });

        $request->session()->forget('workspace_invite_token');

        return redirect()->route('workspaces.show', $workspace->token)
            ->with('success', 'Selamat datang di workspace '.$workspace->name.'!');
    }

    public function declineViaLink(Request $request, string $token): RedirectResponse
    {
        $request->session()->forget('workspace_invite_token');

        return redirect()->route('dashboard')
            ->with('info', 'Kamu menolak undangan workspace.');
    }

    private function acceptPendingInvitation(string $token, User $user): void
    {
        DB::transaction(function () use ($token, $user): void {
            $invitationId = Invitation::findByPlainTextToken($token)?->id;
            $invitation = $invitationId
                ? Invitation::query()->lockForUpdate()->find($invitationId)
                : null;

            if (! $invitation?->isUsable()) {
                abort(422, 'Invitation is invalid, expired, or has been revoked.');
            }

            if (strcasecmp($user->email, $invitation->email) !== 0) {
                abort(403, 'Gunakan akun dengan email yang sama dengan undangan.');
            }

            if ($invitation->type === 'workspace') {
                abort_unless(
                    array_key_exists($invitation->role, Workspace::INVITABLE_ROLE_LABELS),
                    422,
                    'Invitation role is invalid.',
                );

                Workspace::query()->findOrFail($invitation->invitable_id)
                    ->members()
                    ->syncWithoutDetaching([
                        $user->id => [
                            'role' => $invitation->role,
                            'invited_by' => $invitation->invited_by,
                            'status' => 'active',
                            'joined_at' => now(),
                        ],
                    ]);
            } else {
                Project::query()->findOrFail($invitation->invitable_id)
                    ->members()
                    ->syncWithoutDetaching($user->id);
            }

            $invitation->update([
                'status' => Invitation::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'pending_key' => null,
            ]);
        });
    }
}
