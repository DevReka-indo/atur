<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    public const EVENT_WORKSPACE_MEMBER_ADDED = 'workspace.member_added';

    public const EVENT_WORKSPACE_INVITATION_SENT = 'workspace.invitation_sent';

    public const EVENT_WORKSPACE_INVITATION_RESENT = 'workspace.invitation_resent';

    public const EVENT_WORKSPACE_INVITATION_REVOKED = 'workspace.invitation_revoked';

    public const EVENT_WORKSPACE_INVITATION_ACCEPTED = 'workspace.invitation_accepted';

    public const EVENT_WORKSPACE_INVITE_LINK_REGENERATED = 'workspace.invite_link_regenerated';

    public const EVENT_WORKSPACE_INVITE_LINK_DISABLED = 'workspace.invite_link_disabled';

    public const EVENT_WORKSPACE_JOINED_VIA_INVITE_LINK = 'workspace.joined_via_invite_link';

    public const EVENT_WORKSPACE_MEMBER_ROLE_CHANGED = 'workspace.member_role_changed';

    public const EVENT_WORKSPACE_MEMBER_REMOVED = 'workspace.member_removed';

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'description',
        'old_value',
        'new_value',
    ];

    protected $casts = [
        'old_value' => 'array',
        'new_value' => 'array',
        'created_at' => 'datetime',
    ];

    public static function actionForWorkspaceEvent(string $event): string
    {
        return match ($event) {
            self::EVENT_WORKSPACE_MEMBER_ADDED,
            self::EVENT_WORKSPACE_INVITATION_SENT,
            self::EVENT_WORKSPACE_INVITATION_ACCEPTED,
            self::EVENT_WORKSPACE_JOINED_VIA_INVITE_LINK => 'assigned',
            default => 'updated',
        };
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function describeWorkspaceEvent(
        string $event,
        array $metadata,
        bool $showInvitationEmail = true,
    ): string {
        $targetName = $metadata['target_name']
            ?? $metadata['target_email']
            ?? 'anggota';
        $targetEmail = (string) ($metadata['target_email'] ?? '');

        if (! $showInvitationEmail && $targetEmail !== '') {
            $targetEmail = self::maskEmail($targetEmail);
        }

        return match ($event) {
            self::EVENT_WORKSPACE_MEMBER_ADDED => sprintf(
                'menambahkan %s sebagai %s.',
                $targetName,
                $metadata['role_label'] ?? 'anggota workspace',
            ),
            self::EVENT_WORKSPACE_INVITATION_SENT => sprintf(
                'mengirim undangan workspace ke %s sebagai %s.',
                $targetEmail,
                $metadata['role_label'] ?? 'anggota workspace',
            ),
            self::EVENT_WORKSPACE_INVITATION_RESENT => sprintf(
                'mengirim ulang undangan ke %s.',
                $targetEmail,
            ),
            self::EVENT_WORKSPACE_INVITATION_REVOKED => sprintf(
                'mencabut undangan untuk %s.',
                $targetEmail,
            ),
            self::EVENT_WORKSPACE_INVITATION_ACCEPTED => sprintf(
                'menerima undangan dan bergabung sebagai %s.',
                $metadata['role_label'] ?? 'anggota workspace',
            ),
            self::EVENT_WORKSPACE_INVITE_LINK_REGENERATED => 'membuat ulang invite link workspace.',
            self::EVENT_WORKSPACE_INVITE_LINK_DISABLED => 'menonaktifkan invite link workspace.',
            self::EVENT_WORKSPACE_JOINED_VIA_INVITE_LINK => sprintf(
                'bergabung melalui invite link sebagai %s.',
                $metadata['role_label'] ?? 'anggota workspace',
            ),
            self::EVENT_WORKSPACE_MEMBER_ROLE_CHANGED => sprintf(
                'mengubah role %s dari %s menjadi %s.',
                $targetName,
                $metadata['old_role_label'] ?? 'role sebelumnya',
                $metadata['role_label'] ?? 'role baru',
            ),
            self::EVENT_WORKSPACE_MEMBER_REMOVED => sprintf(
                'menghapus %s dari workspace.',
                $targetName,
            ),
            default => (string) ($metadata['description'] ?? 'memperbarui workspace.'),
        };
    }

    /**
     * @return array{icon: string, bg: string, color: string}
     */
    public function presentation(): array
    {
        return match ($this->event) {
            self::EVENT_WORKSPACE_MEMBER_ADDED => [
                'icon' => 'fa-user-plus',
                'bg' => 'bg-emerald-100',
                'color' => 'text-emerald-600',
            ],
            self::EVENT_WORKSPACE_INVITATION_SENT,
            self::EVENT_WORKSPACE_INVITATION_RESENT => [
                'icon' => 'fa-envelope',
                'bg' => 'bg-blue-100',
                'color' => 'text-blue-600',
            ],
            self::EVENT_WORKSPACE_INVITATION_REVOKED,
            self::EVENT_WORKSPACE_INVITE_LINK_DISABLED => [
                'icon' => 'fa-ban',
                'bg' => 'bg-red-100',
                'color' => 'text-red-600',
            ],
            self::EVENT_WORKSPACE_INVITATION_ACCEPTED,
            self::EVENT_WORKSPACE_JOINED_VIA_INVITE_LINK => [
                'icon' => 'fa-user-check',
                'bg' => 'bg-emerald-100',
                'color' => 'text-emerald-600',
            ],
            self::EVENT_WORKSPACE_INVITE_LINK_REGENERATED => [
                'icon' => 'fa-rotate',
                'bg' => 'bg-indigo-100',
                'color' => 'text-indigo-600',
            ],
            self::EVENT_WORKSPACE_MEMBER_ROLE_CHANGED => [
                'icon' => 'fa-user-pen',
                'bg' => 'bg-amber-100',
                'color' => 'text-amber-600',
            ],
            self::EVENT_WORKSPACE_MEMBER_REMOVED => [
                'icon' => 'fa-user-minus',
                'bg' => 'bg-red-100',
                'color' => 'text-red-600',
            ],
            default => $this->legacyPresentation(),
        };
    }

    public function displayDescription(bool $showInvitationEmail = true): string
    {
        if (! $this->event) {
            return (string) $this->description;
        }

        return self::describeWorkspaceEvent(
            $this->event,
            $this->new_value ?? [],
            $showInvitationEmail,
        );
    }

    public function getEventAttribute(): ?string
    {
        $event = $this->new_value['event'] ?? null;

        return is_string($event) ? $event : null;
    }

    // Relationships

    /**
     * User yang melakukan action ini
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get entity yang terkait (polymorphic)
     */
    public function entity()
    {
        $models = [
            'workspace' => Workspace::class,
            'project' => Project::class,
            'task' => Task::class,
            'comment' => TaskComment::class,
            'attachment' => TaskAttachment::class,
        ];

        $modelClass = $models[$this->entity_type] ?? null;

        if ($modelClass) {
            return $modelClass::find($this->entity_id);
        }

        return null;
    }

    /**
     * Get nama entity yang terkait
     */
    public function getEntityNameAttribute(): ?string
    {
        $models = [
            'workspace' => Workspace::class,
            'project' => Project::class,
            'task' => Task::class,
            'comment' => TaskComment::class,
            'attachment' => TaskAttachment::class,
        ];

        $modelClass = $models[$this->entity_type] ?? null;
        if (! $modelClass) {
            return null;
        }

        $entity = $modelClass::find($this->entity_id);
        if (! $entity) {
            return null;
        }

        return match ($this->entity_type) {
            'task' => $entity->name,
            'project' => $entity->name,
            'workspace' => $entity->name,
            'comment' => 'komentar di task #'.$entity->task_id,
            'attachment' => $entity->file_name,
            default => null,
        };
    }

    /**
     * Scope untuk filter by entity type
     */
    public function scopeByEntityType($query, $type)
    {
        return $query->where('entity_type', $type);
    }

    /**
     * Scope untuk filter by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function getEntityUrlAttribute(): ?string
    {
        try {
            return match ($this->entity_type) {
                'task' => \App\Models\Task::find($this->entity_id)?->token
                    ? route('tasks.show', \App\Models\Task::find($this->entity_id)->token)
                    : null,
                'project' => \App\Models\Project::find($this->entity_id)?->token
                    ? route('projects.show', \App\Models\Project::find($this->entity_id)->token)
                    : null,
                'workspace' => \App\Models\Workspace::find($this->entity_id)?->token
                    ? route('workspaces.show', \App\Models\Workspace::find($this->entity_id)->token)
                    : null,
                'comment' => \App\Models\Task::find($this->entity_id)?->token
                    ? route('tasks.show', \App\Models\Task::find($this->entity_id)->token)
                    : null,
                'attachment' => \App\Models\Task::find(
                    \App\Models\TaskAttachment::find($this->entity_id)?->task_id
                )?->token
                    ? route('tasks.show', \App\Models\Task::find(
                        \App\Models\TaskAttachment::find($this->entity_id)?->task_id
                    )->token)
                    : null,
                default => null,
            };
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array{icon: string, bg: string, color: string}
     */
    private function legacyPresentation(): array
    {
        return match ($this->action) {
            'created' => ['icon' => 'fa-plus', 'bg' => 'bg-emerald-100', 'color' => 'text-emerald-600'],
            'updated' => ['icon' => 'fa-pen', 'bg' => 'bg-amber-50', 'color' => 'text-amber-500'],
            'deleted' => ['icon' => 'fa-trash-can', 'bg' => 'bg-red-50', 'color' => 'text-red-500'],
            'status_changed' => [
                'icon' => 'fa-arrow-right-arrow-left',
                'bg' => 'bg-purple-100',
                'color' => 'text-purple-600',
            ],
            'assigned' => ['icon' => 'fa-user-plus', 'bg' => 'bg-amber-100', 'color' => 'text-amber-600'],
            'commented' => ['icon' => 'fa-comments', 'bg' => 'bg-sky-100', 'color' => 'text-sky-600'],
            default => ['icon' => 'fa-circle-info', 'bg' => 'bg-gray-100', 'color' => 'text-gray-500'],
        };
    }

    private static function maskEmail(string $email): string
    {
        [$localPart, $domain] = array_pad(explode('@', $email, 2), 2, '');

        if ($domain === '') {
            return '***';
        }

        return mb_substr($localPart, 0, 1).'***@'.$domain;
    }
}
