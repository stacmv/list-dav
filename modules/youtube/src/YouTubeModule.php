<?php

declare(strict_types=1);

namespace ListDAV\Module\YouTube;

use ListDAV\Core\Config;
use ListDAV\Core\Container;
use ListDAV\Core\Database;
use ListDAV\Module\AbstractModule;
use ListDAV\Module\ModuleCapabilities;
use ListDAV\Module\YouTube\Collections\PlaylistsCollection;
use ListDAV\Module\YouTube\Collections\StatusViewCollection;
use ListDAV\Module\YouTube\Collections\YouTubeRootCollection;
use ListDAV\Module\YouTube\Services\WatchStatusTracker;
use ListDAV\Module\YouTube\Services\YouTubeClient;
use Sabre\DAV\ICollection;

class YouTubeModule extends AbstractModule
{
    private YouTubeClient $client;
    private WatchStatusTracker $statusTracker;
    private YouTubeRootCollection $rootCollection;

    public function getName(): string
    {
        return 'youtube';
    }

    public function getVersion(): string
    {
        return '1.0.0';
    }

    public function getDescription(): string
    {
        return 'YouTube playlist management as virtual file system';
    }

    public function initialize(Container $container): void
    {
        parent::initialize($container);

        /** @var Config $config */
        $config = $container->get(Config::class);

        /** @var Database $db */
        $db = $container->get(Database::class);

        $this->client = new YouTubeClient(
            $config->get('youtube.client_id', ''),
            $config->get('youtube.client_secret', ''),
            $db
        );

        $this->statusTracker = new WatchStatusTracker($db);

        $this->rootCollection = new YouTubeRootCollection(
            $this->client,
            $this->statusTracker
        );
    }

    public function getRootCollection(): ICollection
    {
        return $this->rootCollection;
    }

    /**
     * @return array<string, ICollection>
     */
    public function getViewCollections(): array
    {
        return [
            'youtube-new' => new StatusViewCollection(
                'youtube-new',
                $this->client,
                $this->statusTracker,
                ['new']
            ),
            'youtube-watched' => new StatusViewCollection(
                'youtube-watched',
                $this->client,
                $this->statusTracker,
                ['youtube_watched']
            ),
            'youtube-confirmed' => new StatusViewCollection(
                'youtube-confirmed',
                $this->client,
                $this->statusTracker,
                ['watched']
            ),
            'youtube-to-review' => new StatusViewCollection(
                'youtube-to-review',
                $this->client,
                $this->statusTracker,
                ['youtube_watched']
            ),
        ];
    }

    public function getCapabilities(): ModuleCapabilities
    {
        return new ModuleCapabilities(
            read: true,
            write: true,
            delete: true,
            createFolders: true,
            customProperties: ['watched', 'duration', 'publishedAt'],
            acceptsFrom: ['todo', 'calendar'],
            fileFormats: ['url', 'json', 'webloc'],
            defaultFormat: 'url',
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigSchema(): array
    {
        return [
            'client_id' => [
                'type' => 'string',
                'required' => true,
                'label' => 'YouTube API Client ID',
            ],
            'client_secret' => [
                'type' => 'string',
                'required' => true,
                'label' => 'YouTube API Client Secret',
            ],
            'default_format' => [
                'type' => 'select',
                'options' => ['url', 'json', 'webloc'],
                'default' => 'url',
                'label' => 'Default file format',
            ],
        ];
    }

    public function getClient(): YouTubeClient
    {
        return $this->client;
    }

    public function getStatusTracker(): WatchStatusTracker
    {
        return $this->statusTracker;
    }
}
