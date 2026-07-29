<?php

namespace App\Services;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceChatMentionParser
{
    public const MAX_MENTIONS = 10;

    private const MARKER_PATTERN = '~@\[(?<label>[^\]\r\n]{1,255})\]\(user:(?<id>[1-9][0-9]*)\)~u';

    /**
     * @return array{content: string, user_ids: list<int>}
     */
    public function normalize(
        Workspace $workspace,
        string $content,
    ): array {
        preg_match_all(self::MARKER_PATTERN, $content, $matches);
        $requestedUserIds = collect($matches['id'] ?? [])
            ->map(fn (string $id): int => (int) $id)
            ->unique()
            ->values();

        if ($requestedUserIds->count() > self::MAX_MENTIONS) {
            throw ValidationException::withMessages([
                'content' => 'Satu pesan hanya dapat menyebut maksimal 10 anggota workspace.',
            ]);
        }

        $mentionableUsers = $this->mentionableUsers($workspace, $requestedUserIds);
        $normalizedContent = preg_replace_callback(
            self::MARKER_PATTERN,
            function (array $match) use ($mentionableUsers): string {
                $user = $mentionableUsers->get((int) $match['id']);

                if ($user === null) {
                    return '@'.$this->safeLabel($match['label']);
                }

                return sprintf(
                    '@[%s](user:%d)',
                    $this->safeLabel($user->name),
                    $user->id,
                );
            },
            $content,
        );

        return [
            'content' => $normalizedContent ?? $content,
            'user_ids' => $mentionableUsers->keys()
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array{type: 'text'|'mention', text: string, user_id: ?int}>
     */
    public function segments(string $content): array
    {
        preg_match_all(
            self::MARKER_PATTERN,
            $content,
            $matches,
            PREG_SET_ORDER | PREG_OFFSET_CAPTURE,
        );

        $segments = [];
        $offset = 0;

        foreach ($matches as $match) {
            $marker = $match[0][0];
            $markerOffset = $match[0][1];

            if ($markerOffset > $offset) {
                $segments[] = [
                    'type' => 'text',
                    'text' => substr($content, $offset, $markerOffset - $offset),
                    'user_id' => null,
                ];
            }

            $segments[] = [
                'type' => 'mention',
                'text' => '@'.$this->safeLabel($match['label'][0]),
                'user_id' => (int) $match['id'][0],
            ];
            $offset = $markerOffset + strlen($marker);
        }

        if ($offset < strlen($content)) {
            $segments[] = [
                'type' => 'text',
                'text' => substr($content, $offset),
                'user_id' => null,
            ];
        }

        return $segments === []
            ? [['type' => 'text', 'text' => $content, 'user_id' => null]]
            : $segments;
    }

    public function plainText(string $content): string
    {
        return preg_replace_callback(
            self::MARKER_PATTERN,
            fn (array $match): string => '@'.$this->safeLabel($match['label']),
            $content,
        ) ?? $content;
    }

    public function notificationExcerpt(string $content): string
    {
        return Str::of($this->plainText($content))
            ->stripTags()
            ->squish()
            ->limit(117, '...')
            ->toString();
    }

    public function renderedContent(string $content): HtmlString
    {
        $html = collect($this->segments($content))
            ->map(function (array $segment): string {
                if ($segment['type'] === 'mention') {
                    return '<span class="rounded bg-blue-50 px-1 py-0.5 font-semibold text-blue-700">'
                        .e($segment['text'])
                        .'</span>';
                }

                return e($segment['text']);
            })
            ->implode('');

        return new HtmlString($html);
    }

    /**
     * @param  Collection<int, int>  $userIds
     * @return Collection<int, User>
     */
    private function mentionableUsers(
        Workspace $workspace,
        Collection $userIds,
    ): Collection {
        if ($userIds->isEmpty()) {
            return collect();
        }

        return User::query()
            ->select(['id', 'name'])
            ->whereIn('id', $userIds)
            ->where('is_active', true)
            ->where(function ($query) use ($workspace): void {
                $query->whereKey($workspace->created_by)
                    ->orWhereHas(
                        'workspaces',
                        fn ($workspaceQuery) => $workspaceQuery->whereKey($workspace->id),
                    );
            })
            ->get()
            ->keyBy('id');
    }

    private function safeLabel(string $label): string
    {
        $label = preg_replace('/[\[\]\(\)\r\n]+/u', '', $label) ?? '';
        $label = Str::of($label)->squish()->limit(100, '')->toString();

        return $label !== '' ? $label : 'User';
    }
}
