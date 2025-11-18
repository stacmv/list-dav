<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Collections;

use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use ListDAV\Module\YouTube\Services\YouTubeClient;
use ListDAV\VirtualFS\VirtualCollection;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\INode;

class PlaylistCollection extends VirtualCollection
{
    private string $playlistId;

    /**
     * @param array<string, mixed> $playlist
     */
    public function __construct(
        private readonly array $playlist,
        private readonly YouTubeClient $client,
        private readonly WatchStatusTracker $statusTracker,
    ) {
        parent::__construct($playlist['title']);
        $this->playlistId = $playlist['id'];
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $videos = $this->client->getPlaylistItems($this->playlistId);

        return array_map(
            fn(array $video) => new VideoFile(
                $video,
                $this->playlistId,
                $this->client,
                $this->statusTracker
            ),
            $videos
        );
    }

    /**
     * @param resource|string|null $data
     */
    public function createFile($name, $data = null): void
    {
        // Extract video ID from filename or data
        $videoId = $this->extractVideoId($name, $data);

        if ($videoId === null) {
            throw new Forbidden('Cannot determine video ID from filename');
        }

        $this->client->addToPlaylist($this->playlistId, $videoId);
    }

    public function delete(): void
    {
        $this->client->deletePlaylist($this->playlistId);
    }

    public function getPlaylistId(): string
    {
        return $this->playlistId;
    }

    /**
     * @param resource|string|null $data
     */
    private function extractVideoId(string $name, $data): ?string
    {
        // Try to extract from filename (e.g., "dQw4w9WgXcQ.url")
        $basename = pathinfo($name, PATHINFO_FILENAME);
        $basename = $this->statusTracker->stripPrefixFromFilename($basename);

        // If it looks like a video ID (11 characters)
        if (preg_match('/^[a-zA-Z0-9_-]{11}$/', $basename)) {
            return $basename;
        }

        // Try to extract from data content
        if ($data !== null) {
            $content = is_resource($data) ? stream_get_contents($data) : $data;
            if (is_string($content)) {
                // Look for YouTube URL patterns
                if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]{11})/', $content, $matches)) {
                    return $matches[1];
                }
                // Look for plain video ID
                if (preg_match('/^[a-zA-Z0-9_-]{11}$/', trim($content))) {
                    return trim($content);
                }
            }
        }

        return null;
    }
}
