<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube\Collections;

use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use ListDAV\Module\YouTube\Services\YouTubeClient;
use ListDAV\VirtualFS\VirtualCollection;
use Sabre\DAV\INode;

class YouTubeRootCollection extends VirtualCollection
{
    public function __construct(
        private readonly YouTubeClient $client,
        private readonly WatchStatusTracker $statusTracker,
    ) {
        parent::__construct('youtube');
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        return [
            new PlaylistsCollection($this->client, $this->statusTracker),
        ];
    }
}
