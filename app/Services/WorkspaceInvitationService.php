<?php

namespace App\Services;

use App\Mail\InvitationMail;
use App\Models\ActivityLog;
use App\Models\Invitation;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceInvitationService
{
    /**
     * @return array{mode: 'member'|'invitation', message: string}
     */
    public function invite(
        Workspace $workspace,
        User $actor,
        string $role,
        ?int $userId,
        ?string $email,
    ): array {
        $user = $userId ? User::query()->findOrFail($userId) : null;
        $normalizedEmail = strtolower(trim((string) ($user?->email ?? $email)));

        if (! $user && $normalizedEmail !== '') {
            $user = User::query()->whereRaw('LOWER(email) = ?', [$normalizedEmail])->first();
        }

        if ($user) {
            $this->addRegisteredUser($workspace, $actor, $user, $role);

            return [
                'mode' => 'member',
                'message' => sprintf(
                    '%s berhasil ditambahkan sebagai %s.',
                    $user->name,
                    Workspace::roleLabel($role),
                ),
            ];
        }

        $this->sendEmailInvitation($workspace, $actor, $normalizedEmail, $role);

        return [
            'mode' => 'invitation',
            'message' => sprintf('Undangan telah dikirim ke %s.', $normalizedEmail),
        ];
    }

    public function resend(Workspace $workspace, Invitation $invitation, User $actor): void
    {
        $plainTextToken = Str::random(64);

        $invitation = DB::transaction(function () use ($workspace, $invitation, $actor, $plainTextToken): Invitation {
            $lockedInvitation = Invitation::query()->lockForUpdate()->findOrFail($invitation->id);
            $this->ensureInvitationBelongsToWorkspace($workspace, $lockedInvitation);

            if (! $lockedInvitation->isUsable()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Undangan tidak lagi aktif dan tidak dapat dikirim ulang.',
                ]);
            }

            $lockedInvitation->update([
                'token' => Invitation::hashToken($plainTextToken),
                'expires_at' => now()->addDays(7),
                'last_sent_at' => now(),
                'invited_by' => $actor->id,
            ]);

            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_INVITATION_RESENT,
                $workspace,
                $actor,
                [
                    'invitation_id' => $lockedInvitation->id,
                    'target_user_id' => null,
                    'target_email' => $lockedInvitation->email,
                    'role' => $lockedInvitation->role,
                    'role_label' => Workspace::roleLabel($lockedInvitation->role),
                    'source' => 'email_invitation',
                    'status' => Invitation::STATUS_PENDING,
                    'expires_at' => $lockedInvitation->expires_at?->toIso8601String(),
                    'inviter_id' => $actor->id,
                ],
            );

            return $lockedInvitation->fresh('inviter');
        });

        $this->queueInvitationMail($workspace, $invitation, $plainTextToken);
    }

    public function revoke(Workspace $workspace, Invitation $invitation, User $actor): void
    {
        DB::transaction(function () use ($workspace, $invitation, $actor): void {
            $lockedInvitation = Invitation::query()->lockForUpdate()->findOrFail($invitation->id);
            $this->ensureInvitationBelongsToWorkspace($workspace, $lockedInvitation);

            if (! $lockedInvitation->isUsable()) {
                throw ValidationException::withMessages([
                    'invitation' => 'Undangan tidak lagi aktif.',
                ]);
            }

            $lockedInvitation->update([
                'revoked_at' => now(),
                'pending_key' => null,
            ]);

            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_INVITATION_REVOKED,
                $workspace,
                $actor,
                [
                    'invitation_id' => $lockedInvitation->id,
                    'target_user_id' => null,
                    'target_email' => $lockedInvitation->email,
                    'role' => $lockedInvitation->role,
                    'role_label' => Workspace::roleLabel($lockedInvitation->role),
                    'source' => 'email_invitation',
                    'status' => 'revoked',
                    'inviter_id' => $lockedInvitation->invited_by,
                ],
            );
        });
    }

    private function addRegisteredUser(
        Workspace $workspace,
        User $actor,
        User $user,
        string $role,
    ): void {
        DB::transaction(function () use ($workspace, $actor, $user, $role): void {
            Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();

            if ($workspace->members()->whereKey($user->id)->exists()) {
                throw ValidationException::withMessages([
                    'candidate' => 'User sudah menjadi anggota workspace ini.',
                ]);
            }

            $workspace->members()->attach($user->id, [
                'role' => $role,
                'invited_by' => $actor->id,
                'status' => 'active',
                'joined_at' => now(),
            ]);

            Invitation::query()
                ->where('type', 'workspace')
                ->where('invitable_id', $workspace->id)
                ->whereRaw('LOWER(email) = ?', [strtolower($user->email)])
                ->pending()
                ->update([
                    'revoked_at' => now(),
                    'pending_key' => null,
                ]);

            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_MEMBER_ADDED,
                $workspace,
                $actor,
                [
                    'target_user_id' => $user->id,
                    'target_name' => $user->name,
                    'target_email' => $user->email,
                    'role' => $role,
                    'role_label' => Workspace::roleLabel($role),
                    'invitation_id' => null,
                    'source' => 'registered_user',
                    'status' => 'active',
                ],
            );
        });
    }

    private function sendEmailInvitation(
        Workspace $workspace,
        User $actor,
        string $email,
        string $role,
    ): void {
        $plainTextToken = Str::random(64);
        $pendingKey = Invitation::pendingKey('workspace', $workspace->id, $email);

        $invitation = DB::transaction(function () use (
            $workspace,
            $actor,
            $email,
            $role,
            $plainTextToken,
            $pendingKey,
        ): Invitation {
            Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();

            $existingInvitation = Invitation::query()
                ->where('type', 'workspace')
                ->where('invitable_id', $workspace->id)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->where('status', Invitation::STATUS_PENDING)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->first();

            if ($existingInvitation?->isUsable()) {
                throw ValidationException::withMessages([
                    'candidate' => 'Email ini masih memiliki undangan aktif. Gunakan aksi kirim ulang.',
                ]);
            }

            if ($existingInvitation) {
                $existingInvitation->update([
                    'status' => 'expired',
                    'pending_key' => null,
                ]);
            }

            $invitation = Invitation::create([
                'email' => $email,
                'token' => Invitation::hashToken($plainTextToken),
                'pending_key' => $pendingKey,
                'type' => 'workspace',
                'invitable_id' => $workspace->id,
                'invited_by' => $actor->id,
                'role' => $role,
                'status' => Invitation::STATUS_PENDING,
                'expires_at' => now()->addDays(7),
                'last_sent_at' => now(),
            ]);

            ActivityLogService::workspaceEvent(
                ActivityLog::EVENT_WORKSPACE_INVITATION_SENT,
                $workspace,
                $actor,
                [
                    'invitation_id' => $invitation->id,
                    'target_user_id' => null,
                    'target_email' => $email,
                    'role' => $role,
                    'role_label' => Workspace::roleLabel($role),
                    'source' => 'email_invitation',
                    'status' => Invitation::STATUS_PENDING,
                    'expires_at' => $invitation->expires_at?->toIso8601String(),
                    'inviter_id' => $actor->id,
                ],
            );

            return $invitation->load('inviter');
        });

        $this->queueInvitationMail($workspace, $invitation, $plainTextToken);
    }

    private function queueInvitationMail(
        Workspace $workspace,
        Invitation $invitation,
        string $plainTextToken,
    ): void {
        Mail::to($invitation->email)->queue(new InvitationMail(
            $invitation,
            $workspace->name,
            $plainTextToken,
            Workspace::roleLabel($invitation->role),
        ));
    }

    private function ensureInvitationBelongsToWorkspace(
        Workspace $workspace,
        Invitation $invitation,
    ): void {
        if ($invitation->type !== 'workspace'
            || (int) $invitation->invitable_id !== (int) $workspace->id) {
            abort(404);
        }
    }
}
