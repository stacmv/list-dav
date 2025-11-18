<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Collections;

use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use ListDAV\Module\YouTube\Services\YouTubeClient;
use ListDAV\VirtualFS\VirtualCollection;
use Sabre\DAV\INode;

class PlaylistsCollection extends VirtualCollection
{
    public function __construct(
        private readonly YouTubeClient $client,
        private readonly WatchStatusTracker $statusTracker,
    ) {
        parent::__construct('playlists');
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $playlists = $this->client->getPlaylists();

        return array_map(
            fn(array $playlist) => new PlaylistCollection(
                $playlist,
                $this->client,
                $this->statusTracker
            ),
            $playlists
        );
    }

    public function createDirectory($name): void
    {
        $this->client->createPlaylist($name);
    }
}
