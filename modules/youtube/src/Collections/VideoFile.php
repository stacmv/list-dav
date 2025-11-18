<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Collections;

use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use ListDAV\Module\YouTube\Services\YouTubeClient;
use ListDAV\VirtualFS\VirtualFile;

class VideoFile extends VirtualFile
{
    private string $videoId;
    private string $playlistItemId;

    /**
     * @param array<string, mixed> $video
     */
    public function __construct(
        private readonly array $video,
        private readonly string $playlistId,
        private readonly YouTubeClient $client,
        private readonly WatchStatusTracker $statusTracker,
    ) {
        $this->videoId = $video['id'];
        $this->playlistItemId = $video['playlistItemId'] ?? '';

        $prefix = $this->statusTracker->getDisplayPrefix($this->videoId);
        $title = $this->sanitizeFilename($video['title']);
        $name = "{$prefix} {$title}.url";

        parent::__construct($name);
    }

    public function get(): string
    {
        // Return .url format (Windows Internet Shortcut)
        $url = "https://www.youtube.com/watch?v={$this->videoId}";

        return "[InternetShortcut]\r\nURL={$url}\r\n";
    }

    public function getSize(): int
    {
        return strlen($this->get());
    }

    public function getContentType(): ?string
    {
        return 'application/x-url';
    }

    public function delete(): void
    {
        if ($this->playlistItemId !== '') {
            $this->client->removeFromPlaylist($this->playlistItemId);
        }
    }

    public function setName($name): void
    {
        // Parse status from new filename
        $newStatus = $this->statusTracker->parseStatusFromFilename($name);

        if ($newStatus !== null) {
            $this->statusTracker->setStatus($this->videoId, $newStatus);
        }

        // Update internal name
        $this->name = $name;
    }

    public function getVideoId(): string
    {
        return $this->videoId;
    }

    public function getPlaylistItemId(): string
    {
        return $this->playlistItemId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getVideoData(): array
    {
        return $this->video;
    }

    /**
     * Get extended properties for WebDAV PROPFIND
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        $status = $this->statusTracker->getStatus($this->videoId);

        return [
            'videoId' => $this->videoId,
            'title' => $this->video['title'],
            'duration' => $this->video['duration'] ?? '',
            'playlistId' => $this->playlistId,
            'userStatus' => $status['user_status'] ?? 'unmarked',
            'youtubeWatched' => (bool) ($status['youtube_watched'] ?? false),
            'url' => "https://www.youtube.com/watch?v={$this->videoId}",
        ];
    }

    private function sanitizeFilename(string $filename): string
    {
        // Remove or replace characters not allowed in filenames
        $filename = preg_replace('/[<>:"\/\\|?*]/', '', $filename);
        $filename = preg_replace('/\s+/', ' ', $filename ?? '');
        return trim($filename ?? '');
    }
}
