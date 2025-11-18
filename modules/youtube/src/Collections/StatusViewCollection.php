<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Collections;

use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use ListDAV\Module\YouTube\Services\YouTubeClient;
use ListDAV\VirtualFS\VirtualCollection;
use Sabre\DAV\INode;

class StatusViewCollection extends VirtualCollection
{
    /**
     * @param array<string> $statuses
     */
    public function __construct(
        string $name,
        private readonly YouTubeClient $client,
        private readonly WatchStatusTracker $statusTracker,
        private readonly array $statuses,
    ) {
        parent::__construct($name);
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $videos = [];

        // Get all playlists and their videos
        foreach ($this->client->getPlaylists() as $playlist) {
            $playlistVideos = $this->client->getPlaylistItems($playlist['id']);

            foreach ($playlistVideos as $video) {
                $status = $this->statusTracker->getStatus($video['id']);
                $userStatus = $status['user_status'] ?? 'unmarked';
                $youtubeWatched = (bool) ($status['youtube_watched'] ?? false);

                // Determine effective status
                $effectiveStatus = $this->getEffectiveStatus($userStatus, $youtubeWatched);

                if (in_array($effectiveStatus, $this->statuses, true)) {
                    $videos[] = new VideoFile(
                        $video,
                        $playlist['id'],
                        $this->client,
                        $this->statusTracker
                    );
                }
            }
        }

        return $videos;
    }

    private function getEffectiveStatus(string $userStatus, bool $youtubeWatched): string
    {
        if ($userStatus === WatchStatusTracker::STATUS_WATCHED) {
            return 'watched';
        }

        if ($userStatus === WatchStatusTracker::STATUS_SKIPPED) {
            return 'skipped';
        }

        // userStatus is 'unmarked'
        return $youtubeWatched ? 'youtube_watched' : 'new';
    }
}
