<?php

namespace App\Http\Controllers;

use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use App\Services\WorkspaceInvitationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class WorkspaceInvitationController extends Controller
{
    public function __construct(
        private readonly WorkspaceInvitationService $invitationService,
    ) {}

    public function candidates(Request $request, string $token): JsonResponse
    {
        $workspace = $this->authorizedWorkspace($request, $token);
        $validated = $request->validate([
            'search' => ['required', 'string', 'min:2', 'max:255'],
        ]);
        $search = trim($validated['search']);

        $users = User::query()
            ->select(['id', 'name', 'email', 'profile_photo'])
            ->withExists([
                'workspaces as already_member' => fn ($query) => $query->whereKey($workspace->id),
            ])
            ->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->limit(10)
            ->get()
            ->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'avatar_url' => $user->profile_photo
                    ? asset('storage/'.$user->profile_photo)
                    : null,
                'membership_status' => $user->already_member ? 'already_member' : 'available',
            ]);

        $normalizedEmail = filter_var($search, FILTER_VALIDATE_EMAIL)
            ? strtolower($search)
            : null;
        $pendingInvitation = $normalizedEmail
            ? Invitation::query()
                ->where('type', 'workspace')
                ->where('invitable_id', $workspace->id)
                ->whereRaw('LOWER(email) = ?', [$normalizedEmail])
                ->pending()
                ->where('expires_at', '>', now())
                ->exists()
            : false;

        return response()->json([
            'data' => $users,
            'email' => [
                'value' => $normalizedEmail,
                'has_pending_invitation' => $pendingInvitation,
            ],
        ]);
    }

    public function store(Request $request, string $token): RedirectResponse
    {
        $workspace = $this->authorizedWorkspace($request, $token);

        if ($request->filled('email')) {
            $request->merge([
                'email' => strtolower(trim($request->string('email')->toString())),
            ]);
        }

        $validated = $request->validate([
            'user_id' => ['nullable', 'integer', 'exists:users,id', 'required_without:email'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:255', 'required_without:user_id'],
            'role' => ['required', Rule::in(array_keys(Workspace::INVITABLE_ROLE_LABELS))],
        ]);

        $result = $this->invitationService->invite(
            $workspace,
            $request->user(),
            $validated['role'],
            isset($validated['user_id']) ? (int) $validated['user_id'] : null,
            $validated['email'] ?? null,
        );

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', $result['message']);
    }

    public function resend(
        Request $request,
        string $token,
        Invitation $invitation,
    ): RedirectResponse {
        $workspace = $this->authorizedWorkspace($request, $token);
        $this->invitationService->resend($workspace, $invitation, $request->user());

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Undangan berhasil dikirim ulang.');
    }

    public function revoke(
        Request $request,
        string $token,
        Invitation $invitation,
    ): RedirectResponse {
        $workspace = $this->authorizedWorkspace($request, $token);
        $this->invitationService->revoke($workspace, $invitation, $request->user());

        return redirect(route('workspaces.show', $workspace->token).'?tab=members')
            ->with('success', 'Undangan berhasil dibatalkan.');
    }

    private function authorizedWorkspace(Request $request, string $token): Workspace
    {
        $workspace = Workspace::query()->where('token', $token)->firstOrFail();

        abort_unless($workspace->canManageMembers($request->user()), 403);

        return $workspace;
    }
}
