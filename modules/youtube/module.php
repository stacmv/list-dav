<?php

declare(strict_types=1);

require_once __DIR__ . '/src/YouTubeModule.php';
require_once __DIR__ . '/src/Services/YouTubeClient.php';
require_once __DIR__ . '/src/Services/WatchStatusTracker.php';
require_once __DIR__ . '/src/Collections/YouTubeRootCollection.php';
require_once __DIR__ . '/src/Collections/PlaylistsCollection.php';
require_once __DIR__ . '/src/Collections/PlaylistCollection.php';
require_once __DIR__ . '/src/Collections/VideoFile.php';
require_once __DIR__ . '/src/Collections/StatusViewCollection.php';

return new ListDAV\Module\YouTube\YouTubeModule();
