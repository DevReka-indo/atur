<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Task;
use Illuminate\Database\Eloquent\Builder;

class NotificationPresentationService
{
    public const FILTER_ALL = 'all';

    public const FILTER_UNREAD = 'unread';

    public const FILTER_MENTIONS = 'mentions';

    public const FILTER_TASKS = 'tasks';

    public const FILTER_PROJECTS = 'projects';

    public const FILTER_ALERTS = 'alerts';

    /**
     * @var array<string, string>
     */
    public const FILTER_LABELS = [
        self::FILTER_ALL => 'All',
        self::FILTER_UNREAD => 'Unread',
        self::FILTER_MENTIONS => 'Mentions',
        self::FILTER_TASKS => 'Tasks',
        self::FILTER_PROJECTS => 'Projects',
        self::FILTER_ALERTS => 'Alerts',
    ];

    private const DANGER_TYPES = [
        'project_overdue',
        'task_overdue',
        'member_overload',
        'project_blocked',
        'system_error',
        'failed_process',
        'urgent_task',
    ];

    private const WARNING_TYPES = [
        'deadline_approaching',
        'deadline_warning',
        'task_due_soon',
        'approval_required',
        'workload_warning',
    ];

    private const INFO_TYPES = [
        Notification::TYPE_WORKSPACE_CHAT_MENTION,
        Notification::TYPE_PROJECT_DISCUSSION_MENTION,
        'assignment',
        'task_assigned',
        'project_added',
        'project_assigned',
        'status_change',
        'status_changed',
        'comment_added',
        'member_added',
        'project_updated',
    ];

    private const SUCCESS_TYPES = [
        'task_completed',
        'project_completed',
        'approval_accepted',
        'process_success',
    ];

    private const MENTION_TYPES = [
        Notification::TYPE_WORKSPACE_CHAT_MENTION,
        Notification::TYPE_PROJECT_DISCUSSION_MENTION,
    ];

    private const TASK_TYPES = [
        'assignment',
        'task_assigned',
        'status_change',
        'status_changed',
        'deadline_approaching',
        'deadline_warning',
        'task_due_soon',
        'task_completed',
        'task_overdue',
        'urgent_task',
    ];

    private const PROJECT_TYPES = [
        'project_added',
        'project_assigned',
        'member_added',
        'project_updated',
        'project_completed',
        'project_overdue',
        'project_blocked',
    ];

    /**
     * @return array{
     *     severity: string,
     *     category: string,
     *     icon: string,
     *     icon_classes: string,
     *     card_classes: string,
     *     accent_classes: string,
     *     title_classes: string,
     *     title: string,
     *     description: string,
     *     context_label: ?string,
     *     context_icon: string,
     *     action_url: ?string,
     *     action_label: string,
     *     is_unread: bool,
     *     severity_label: string
     * }
     */
    public function forNotification(Notification $notification): array
    {
        $severity = $this->severity($notification->type);
        $category = $this->category($notification->type);
        $isUnread = ! $notification->isRead();
        $style = $this->severityStyle($severity);

        return [
            'severity' => $severity,
            'category' => $category,
            'icon' => $this->icon($notification->type, $category, $severity),
            'icon_classes' => $style['icon'],
            'card_classes' => $isUnread ? $style['unread_background'] : 'bg-white',
            'accent_classes' => $isUnread ? $style['accent'] : $style['read_accent'],
            'title_classes' => $isUnread ? 'font-semibold text-slate-950' : 'font-medium text-slate-800',
            'title' => $notification->title,
            'description' => $notification->message,
            'context_label' => $this->contextLabel($notification),
            'context_icon' => $this->contextIcon($category),
            'action_url' => $notification->targetUrl(),
            'action_label' => $this->actionLabel($category),
            'is_unread' => $isUnread,
            'severity_label' => ucfirst($severity),
        ];
    }

    public function severity(string $type): string
    {
        return match (true) {
            in_array($type, self::DANGER_TYPES, true) => 'danger',
            in_array($type, self::WARNING_TYPES, true) => 'warning',
            in_array($type, self::INFO_TYPES, true) => 'info',
            in_array($type, self::SUCCESS_TYPES, true) => 'success',
            default => 'neutral',
        };
    }

    public function category(string $type): string
    {
        return match (true) {
            in_array($type, self::MENTION_TYPES, true) => self::FILTER_MENTIONS,
            in_array($type, self::TASK_TYPES, true) => self::FILTER_TASKS,
            in_array($type, self::PROJECT_TYPES, true) => self::FILTER_PROJECTS,
            in_array($type, $this->alertTypes(), true) => self::FILTER_ALERTS,
            default => 'general',
        };
    }

    public function normalizeFilter(?string $filter): string
    {
        return array_key_exists((string) $filter, self::FILTER_LABELS)
            ? (string) $filter
            : self::FILTER_ALL;
    }

    public function applyFilter(Builder $query, string $filter): Builder
    {
        return match ($filter) {
            self::FILTER_UNREAD => $query->whereNull('read_at'),
            self::FILTER_MENTIONS => $query->whereIn('type', self::MENTION_TYPES),
            self::FILTER_TASKS => $query->whereIn('type', self::TASK_TYPES),
            self::FILTER_PROJECTS => $query->whereIn('type', self::PROJECT_TYPES),
            self::FILTER_ALERTS => $query->whereIn('type', $this->alertTypes()),
            default => $query,
        };
    }

    /**
     * @return array<string, int>
     */
    public function filterCounts(int $userId): array
    {
        $counts = Notification::query()
            ->where('user_id', $userId)
            ->toBase()
            ->selectRaw('COUNT(*) as all_count')
            ->selectRaw('SUM(CASE WHEN read_at IS NULL THEN 1 ELSE 0 END) as unread_count')
            ->selectRaw(
                'SUM(CASE WHEN type IN ('.$this->placeholders(self::MENTION_TYPES).') THEN 1 ELSE 0 END) as mentions_count',
                self::MENTION_TYPES,
            )
            ->selectRaw(
                'SUM(CASE WHEN type IN ('.$this->placeholders(self::TASK_TYPES).') THEN 1 ELSE 0 END) as tasks_count',
                self::TASK_TYPES,
            )
            ->selectRaw(
                'SUM(CASE WHEN type IN ('.$this->placeholders(self::PROJECT_TYPES).') THEN 1 ELSE 0 END) as projects_count',
                self::PROJECT_TYPES,
            )
            ->selectRaw(
                'SUM(CASE WHEN type IN ('.$this->placeholders($this->alertTypes()).') THEN 1 ELSE 0 END) as alerts_count',
                $this->alertTypes(),
            )
            ->first();

        return [
            self::FILTER_ALL => (int) ($counts->all_count ?? 0),
            self::FILTER_UNREAD => (int) ($counts->unread_count ?? 0),
            self::FILTER_MENTIONS => (int) ($counts->mentions_count ?? 0),
            self::FILTER_TASKS => (int) ($counts->tasks_count ?? 0),
            self::FILTER_PROJECTS => (int) ($counts->projects_count ?? 0),
            self::FILTER_ALERTS => (int) ($counts->alerts_count ?? 0),
        ];
    }

    /**
     * @return array{
     *     label: string,
     *     badge_classes: string,
     *     card_classes: string,
     *     icon: string,
     *     due_date: string
     * }
     */
    public function forDeadline(Task $task): array
    {
        $daysUntilDue = (int) now()
            ->startOfDay()
            ->diffInDays($task->due_date->copy()->startOfDay(), false);

        if ($daysUntilDue < 0) {
            $label = 'Overdue';
            $badgeClasses = 'bg-red-50 text-red-700 ring-red-200';
            $cardClasses = 'border-red-200 bg-red-50/50';
            $icon = 'fa-triangle-exclamation';
        } elseif ($daysUntilDue === 0) {
            $label = 'Due today';
            $badgeClasses = 'bg-amber-50 text-amber-700 ring-amber-200';
            $cardClasses = 'border-amber-200 bg-amber-50/40';
            $icon = 'fa-clock';
        } elseif ($daysUntilDue === 1) {
            $label = 'Due tomorrow';
            $badgeClasses = 'bg-amber-50 text-amber-700 ring-amber-200';
            $cardClasses = 'border-amber-200 bg-amber-50/40';
            $icon = 'fa-calendar-day';
        } else {
            $label = "Due in {$daysUntilDue} days";
            $badgeClasses = 'bg-blue-50 text-blue-700 ring-blue-200';
            $cardClasses = 'border-slate-200 bg-white';
            $icon = 'fa-calendar';
        }

        return [
            'label' => $label,
            'badge_classes' => $badgeClasses,
            'card_classes' => $cardClasses,
            'icon' => $icon,
            'due_date' => $task->due_date->format('d M Y'),
        ];
    }

    /**
     * @return list<string>
     */
    private function alertTypes(): array
    {
        return array_values(array_unique([
            ...self::DANGER_TYPES,
            ...self::WARNING_TYPES,
        ]));
    }

    /**
     * @return array{icon: string, accent: string, read_accent: string, unread_background: string}
     */
    private function severityStyle(string $severity): array
    {
        return match ($severity) {
            'danger' => [
                'icon' => 'bg-red-50 text-red-600',
                'accent' => 'border-l-red-500',
                'read_accent' => 'border-l-red-300',
                'unread_background' => 'bg-red-50/50',
            ],
            'warning' => [
                'icon' => 'bg-amber-50 text-amber-600',
                'accent' => 'border-l-amber-500',
                'read_accent' => 'border-l-amber-300',
                'unread_background' => 'bg-amber-50/40',
            ],
            'info' => [
                'icon' => 'bg-blue-50 text-blue-600',
                'accent' => 'border-l-blue-500',
                'read_accent' => 'border-l-blue-300',
                'unread_background' => 'bg-blue-50/30',
            ],
            'success' => [
                'icon' => 'bg-emerald-50 text-emerald-600',
                'accent' => 'border-l-emerald-500',
                'read_accent' => 'border-l-emerald-300',
                'unread_background' => 'bg-emerald-50/30',
            ],
            default => [
                'icon' => 'bg-slate-100 text-slate-600',
                'accent' => 'border-l-slate-400',
                'read_accent' => 'border-l-slate-300',
                'unread_background' => 'bg-slate-50',
            ],
        };
    }

    private function icon(string $type, string $category, string $severity): string
    {
        return match (true) {
            $type === Notification::TYPE_WORKSPACE_CHAT_MENTION => 'fa-at',
            $type === Notification::TYPE_PROJECT_DISCUSSION_MENTION => 'fa-comments',
            $type === 'assignment' || $type === 'task_assigned' => 'fa-user-check',
            $type === 'status_change' || $type === 'status_changed' => 'fa-arrows-rotate',
            $type === 'task_completed' || $type === 'project_completed' => 'fa-circle-check',
            $category === self::FILTER_PROJECTS => 'fa-diagram-project',
            $severity === 'danger' => 'fa-triangle-exclamation',
            $severity === 'warning' => 'fa-clock',
            default => 'fa-bell',
        };
    }

    private function contextLabel(Notification $notification): ?string
    {
        $projectName = data_get($notification->metadata, 'project_name')
            ?? $notification->project?->name
            ?? $notification->task?->project?->name;
        if (filled($projectName)) {
            return 'Project · '.$projectName;
        }

        $workspaceName = data_get($notification->metadata, 'workspace_name')
            ?? $notification->workspace?->name;
        if (filled($workspaceName)) {
            return 'Workspace · '.$workspaceName;
        }

        $threadTitle = data_get($notification->metadata, 'thread_title');

        return filled($threadTitle) ? 'Discussion · '.$threadTitle : null;
    }

    private function contextIcon(string $category): string
    {
        return match ($category) {
            self::FILTER_MENTIONS => 'fa-comments',
            self::FILTER_TASKS => 'fa-list-check',
            self::FILTER_PROJECTS => 'fa-diagram-project',
            self::FILTER_ALERTS => 'fa-triangle-exclamation',
            default => 'fa-bell',
        };
    }

    private function actionLabel(string $category): string
    {
        return match ($category) {
            self::FILTER_MENTIONS => 'Open discussion',
            self::FILTER_TASKS => 'View task',
            self::FILTER_PROJECTS => 'View project',
            default => 'Open',
        };
    }

    /**
     * @param  list<string>  $values
     */
    private function placeholders(array $values): string
    {
        return implode(', ', array_fill(0, count($values), '?'));
    }
}
