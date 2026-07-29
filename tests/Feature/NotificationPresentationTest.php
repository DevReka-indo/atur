<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Services\NotificationPresentationService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationPresentationTest extends TestCase
{
    #[DataProvider('severityTypes')]
    public function test_notification_type_has_expected_severity(
        string $type,
        string $expectedSeverity,
    ): void {
        $presentation = app(NotificationPresentationService::class);

        $this->assertSame($expectedSeverity, $presentation->severity($type));
    }

    public function test_unread_state_does_not_change_severity_or_make_mentions_dangerous(): void
    {
        $service = app(NotificationPresentationService::class);
        $unread = new Notification([
            'type' => Notification::TYPE_PROJECT_DISCUSSION_MENTION,
            'title' => 'Mentioned',
            'message' => 'You were mentioned.',
        ]);
        $read = clone $unread;
        $read->read_at = now();

        $unreadPresentation = $service->forNotification($unread);
        $readPresentation = $service->forNotification($read);

        $this->assertSame('info', $unreadPresentation['severity']);
        $this->assertSame('info', $readPresentation['severity']);
        $this->assertStringContainsString('bg-blue-50/30', $unreadPresentation['card_classes']);
        $this->assertStringNotContainsString('red', $unreadPresentation['card_classes']);
        $this->assertSame('bg-white', $readPresentation['card_classes']);
    }

    public function test_assignment_is_info_and_unknown_legacy_type_is_neutral(): void
    {
        $service = app(NotificationPresentationService::class);

        $assignment = $service->forNotification(new Notification([
            'type' => 'assignment',
            'title' => 'Assigned',
            'message' => 'Task assigned.',
        ]));
        $legacy = $service->forNotification(new Notification([
            'type' => 'legacy_custom_notice',
            'title' => 'Legacy',
            'message' => 'Legacy notification.',
        ]));

        $this->assertSame('info', $assignment['severity']);
        $this->assertStringContainsString('text-blue-600', $assignment['icon_classes']);
        $this->assertSame('neutral', $legacy['severity']);
        $this->assertStringContainsString('text-slate-600', $legacy['icon_classes']);
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function severityTypes(): iterable
    {
        yield 'overdue' => ['task_overdue', 'danger'];
        yield 'overload' => ['member_overload', 'danger'];
        yield 'deadline' => ['deadline_warning', 'warning'];
        yield 'completed' => ['task_completed', 'success'];
        yield 'assignment' => ['assignment', 'info'];
        yield 'workspace mention' => [Notification::TYPE_WORKSPACE_CHAT_MENTION, 'info'];
        yield 'project mention' => [Notification::TYPE_PROJECT_DISCUSSION_MENTION, 'info'];
        yield 'unknown' => ['old_unknown_type', 'neutral'];
    }
}
