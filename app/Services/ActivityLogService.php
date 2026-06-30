<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log(
        string $action,
        string $entityType,
        int $entityId,
        string $description,
        array $oldValue = null,
        array $newValue = null
    ): void {
        ActivityLog::create([
            'user_id'     => Auth::id(),
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'description' => $description,
            'old_value'   => $oldValue,
            'new_value'   => $newValue,
        ]);
    }
}
