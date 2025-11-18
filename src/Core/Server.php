<?php

declare(strict_types=1);

namespace ListDAV\Core;

use ListDAV\Auth\AuthInterface;
use ListDAV\Auth\AuthPlugin;
use ListDAV\Module\CrossModulePlugin;
use ListDAV\Module\ModuleLoader;
use ListDAV\Module\ModuleRegistry;
use ListDAV\Trash\TrashCollection;
use ListDAV\Trash\TrashManager;
use ListDAV\VirtualFS\RootCollection;
use Sabre\DAV;
use Sabre\DAV\Auth\Plugin as SabreAuthPlugin;

class Server
{
    private DAV\Server $server;
    private ModuleRegistry $registry;

    public function __construct(
        private readonly Container $container,
        private readonly Config $config,
    ) {
        $this->registry = new ModuleRegistry();
        $this->container->instance(ModuleRegistry::class, $this->registry);
    }

    public function initialize(): void
    {
        $this->loadModules();
        $this->createServer();
        $this->setupPlugins();
    }

    private function loadModules(): void
    {
        $loader = new ModuleLoader(
            $this->config->get('app.base_path', getcwd()),
            $this->container
        );

        foreach ($loader->loadAll() as $module) {
            $this->registry->register($module);
        }
    }

    private function createServer(): void
    {
        /** @var Database $db */
        $db = $this->container->get(Database::class);

        $trashManager = new TrashManager(
            $db,
            $this->config->get('trash.retention_days', 30)
        );
        $this->container->instance(TrashManager::class, $trashManager);

        $trash = new TrashCollection($trashManager);
        $root = new RootCollection($this->registry, $trash);

        $this->server = new DAV\Server($root);
        $this->server->setBaseUri('/');
    }

    private function setupPlugins(): void
    {
        // Auth plugin
        /** @var AuthInterface $auth */
        $auth = $this->container->get(AuthInterface::class);
        $authBackend = new AuthPlugin($auth);
        $authBackend->setRealm($this->config->get('auth.realm', 'ListDAV'));

        $authPlugin = new SabreAuthPlugin($authBackend);
        $this->server->addPlugin($authPlugin);

        // Browser plugin for web access
        $this->server->addPlugin(new DAV\Browser\Plugin());

        // Cross-module operation plugin
        $this->server->addPlugin(new CrossModulePlugin($this->registry));

        // Locks plugin
        $locksBackend = new DAV\Locks\Backend\File(
            $this->config->get('app.base_path', getcwd()) . '/var/data/locks'
        );
        $this->server->addPlugin(new DAV\Locks\Plugin($locksBackend));
    }

    public function run(): void
    {
        $this->server->exec();
    }

    public function getServer(): DAV\Server
    {
        return $this->server;
    }

    public function getRegistry(): ModuleRegistry
    {
        return $this->registry;
    }
}
