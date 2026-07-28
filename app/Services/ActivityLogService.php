<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        string $description,
        ?array $oldValue = null,
        ?array $newValue = null,
    ): void {
        ActivityLog::create([
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'description' => $description,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>|null  $oldValue
     */
    public static function workspaceEvent(
        string $event,
        Workspace $workspace,
        User $actor,
        array $metadata = [],
        ?array $oldValue = null,
    ): ActivityLog {
        $metadata = [
            'event' => $event,
            'workspace_id' => $workspace->id,
            ...$metadata,
        ];

        return ActivityLog::create([
            'user_id' => $actor->id,
            'action' => ActivityLog::actionForWorkspaceEvent($event),
            'entity_type' => 'workspace',
            'entity_id' => $workspace->id,
            'description' => ActivityLog::describeWorkspaceEvent($event, $metadata),
            'old_value' => $oldValue,
            'new_value' => $metadata,
        ]);
    }
}
