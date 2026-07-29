<?php

namespace Tests\Feature;

use Tests\TestCase;

class MentionComposerUxTest extends TestCase
{
    public function test_shared_mention_composer_serializes_and_deserializes_internal_markers_safely(): void
    {
        $utility = $this->source('resources/js/mention-composer.js');

        $this->assertStringContainsString('data-mention-user-id', $utility);
        $this->assertStringContainsString('data-mention-name', $utility);
        $this->assertStringContainsString("mention.contentEditable = 'false'", $utility);
        $this->assertStringContainsString('mention.textContent = `@${safeName}`', $utility);
        $this->assertStringContainsString('`@[${name}](user:${userId})`', $utility);
        $this->assertStringContainsString('content.matchAll(MENTION_MARKER_PATTERN)', $utility);
        $this->assertStringContainsString('document.createElement(\'span\')', $utility);
        $this->assertStringContainsString('document.createTextNode', $utility);
        $this->assertStringNotContainsString('innerHTML', $utility);
    }

    public function test_shared_composer_handles_plain_text_paste_atomic_deletion_and_ime(): void
    {
        $utility = $this->source('resources/js/mention-composer.js');

        $this->assertStringContainsString("event.clipboardData?.getData('text/plain')", $utility);
        $this->assertStringContainsString('PLAIN_MARKER_PATTERN', $utility);
        $this->assertStringContainsString("event.key === 'Backspace'", $utility);
        $this->assertStringContainsString("event.key === 'Delete'", $utility);
        $this->assertStringContainsString('mention.remove()', $utility);
        $this->assertStringContainsString("'compositionstart'", $utility);
        $this->assertStringContainsString("'compositionend'", $utility);
        $this->assertStringContainsString('event.preventDefault()', $utility);
    }

    public function test_workspace_and_project_composers_use_accessible_controlled_editors(): void
    {
        $workspaceComposer = $this->source(
            'resources/views/workspaces/partials/show/chat/_composer.blade.php',
        );
        $projectComposer = $this->source(
            'resources/views/discussion/partials/chat/_composer.blade.php',
        );

        foreach ([$workspaceComposer, $projectComposer] as $composer) {
            $this->assertStringContainsString('contenteditable="false"', $composer);
            $this->assertStringContainsString('role="textbox"', $composer);
            $this->assertStringContainsString('aria-multiline="true"', $composer);
            $this->assertStringContainsString('aria-autocomplete="list"', $composer);
            $this->assertStringContainsString('disabled', $composer);
            $this->assertStringNotContainsString('<textarea', $composer);
            $this->assertStringNotContainsString('(user:', $composer);
        }
    }

    public function test_both_chat_clients_send_serialized_plain_text_and_restore_edit_content(): void
    {
        foreach (['resources/js/workspace-chat.js', 'resources/js/project-discussion.js'] as $path) {
            $javascript = $this->source($path);

            $this->assertStringContainsString(
                "from './mention-composer'",
                $javascript,
            );
            $this->assertStringContainsString('.serialize()', $javascript);
            $this->assertStringContainsString('.deserialize(', $javascript);
            $this->assertStringContainsString('JSON.stringify({ content })', $javascript);
            $this->assertStringContainsString('Message cannot exceed 1000 characters.', $javascript);
            $this->assertStringNotContainsString('innerHTML', $javascript);
            $this->assertStringNotContainsString('window.prompt', $javascript);
        }
    }

    public function test_sent_message_mentions_use_consistent_blue_visual_style(): void
    {
        $utility = $this->source('resources/js/mention-composer.js');
        $workspaceParser = $this->source('app/Services/WorkspaceChatMentionParser.php');
        $projectParser = $this->source('app/Services/ProjectDiscussionMentionParser.php');

        foreach ([$utility, $workspaceParser, $projectParser] as $source) {
            $this->assertStringContainsString('bg-blue-50', $source);
            $this->assertStringContainsString('text-blue-700', $source);
        }

        $this->assertStringContainsString('px-1', $utility);
        $this->assertStringContainsString('font-semibold', $utility);
    }

    /**
     * @param  non-empty-string  $path
     */
    private function source(string $path): string
    {
        $source = file_get_contents(base_path($path));

        $this->assertIsString($source);

        return $source;
    }
}
