<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Services;

use ListDAV\Core\Database;

class WatchStatusTracker
{
    public const STATUS_UNMARKED = 'unmarked';
    public const STATUS_WATCHED = 'watched';
    public const STATUS_SKIPPED = 'skipped';

    public const PREFIX_NEW = '[ ]';
    public const PREFIX_YOUTUBE_WATCHED = '[?]';
    public const PREFIX_CONFIRMED = '[✓]';
    public const PREFIX_SKIPPED = '[—]';

    public function __construct(
        private readonly Database $database,
    ) {}

    /**
     * @return array<string, mixed>|null
     */
    public function getStatus(string $videoId): ?array
    {
        $result = $this->database->query(
            'SELECT * FROM youtube_watch_status WHERE video_id = :id',
            ['id' => $videoId]
        );

        return $result[0] ?? null;
    }

    public function setStatus(string $videoId, string $status): void
    {
        $this->database->execute(
            'INSERT OR REPLACE INTO youtube_watch_status (video_id, user_status, updated_at)
             VALUES (:id, :status, datetime(\'now\'))',
            ['id' => $videoId, 'status' => $status]
        );
    }

    public function setYouTubeWatched(string $videoId, bool $watched): void
    {
        $existing = $this->getStatus($videoId);

        if ($existing) {
            $this->database->execute(
                'UPDATE youtube_watch_status SET youtube_watched = :watched, updated_at = datetime(\'now\') WHERE video_id = :id',
                ['id' => $videoId, 'watched' => $watched ? 1 : 0]
            );
        } else {
            $this->database->execute(
                'INSERT INTO youtube_watch_status (video_id, youtube_watched) VALUES (:id, :watched)',
                ['id' => $videoId, 'watched' => $watched ? 1 : 0]
            );
        }
    }

    public function getDisplayPrefix(string $videoId): string
    {
        $status = $this->getStatus($videoId);

        if ($status === null) {
            return self::PREFIX_NEW;
        }

        $userStatus = $status['user_status'];
        $youtubeWatched = (bool) $status['youtube_watched'];

        return match ($userStatus) {
            self::STATUS_WATCHED => self::PREFIX_CONFIRMED,
            self::STATUS_SKIPPED => self::PREFIX_SKIPPED,
            default => $youtubeWatched ? self::PREFIX_YOUTUBE_WATCHED : self::PREFIX_NEW,
        };
    }

    public function parseStatusFromFilename(string $filename): ?string
    {
        if (str_starts_with($filename, self::PREFIX_CONFIRMED)) {
            return self::STATUS_WATCHED;
        }
        if (str_starts_with($filename, self::PREFIX_SKIPPED)) {
            return self::STATUS_SKIPPED;
        }
        if (str_starts_with($filename, self::PREFIX_NEW) || str_starts_with($filename, self::PREFIX_YOUTUBE_WATCHED)) {
            return self::STATUS_UNMARKED;
        }

        return null;
    }

    public function stripPrefixFromFilename(string $filename): string
    {
        $prefixes = [
            self::PREFIX_CONFIRMED,
            self::PREFIX_SKIPPED,
            self::PREFIX_YOUTUBE_WATCHED,
            self::PREFIX_NEW,
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($filename, $prefix)) {
                return trim(substr($filename, strlen($prefix)));
            }
        }

        return $filename;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getVideosByStatus(string $status): array
    {
        if ($status === 'new') {
            return $this->database->query(
                'SELECT video_id FROM youtube_watch_status WHERE user_status = \'unmarked\' AND youtube_watched = 0'
            );
        }

        if ($status === 'youtube_watched') {
            return $this->database->query(
                'SELECT video_id FROM youtube_watch_status WHERE user_status = \'unmarked\' AND youtube_watched = 1'
            );
        }

        return $this->database->query(
            'SELECT video_id FROM youtube_watch_status WHERE user_status = :status',
            ['status' => $status]
        );
    }
}
