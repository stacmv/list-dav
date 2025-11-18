<?php

declare(strict_types=1);

namespace ListDAV\Core;

use ListDAV\Auth\AuthInterface;
use ListDAV\Auth\BasicAuth;
use ListDAV\Module\ModuleLoader;
use ListDAV\Module\ModuleRegistry;
use ListDAV\Trash\TrashCollection;
use ListDAV\Trash\TrashManager;
use ListDAV\VirtualFS\RootCollection;
use ListDAV\WebUI\WebUIHandler;

class Application
{
    private Container $container;
    private Config $config;
    private ?Server $server = null;

    public function __construct(string $basePath)
    {
        $this->container = new Container();
        $this->config = new Config($basePath);
        $this->config->set('app.base_path', $basePath);

        $this->container->instance(Config::class, $this->config);
        $this->container->instance(Container::class, $this->container);

        $this->registerServices();
    }

    private function registerServices(): void
    {
        // Database
        $this->container->singleton(Database::class, function () {
            $path = $this->config->get('app.base_path') . '/' . $this->config->get('database.path');
            return new Database($path);
        });

        // Auth
        $this->container->singleton(AuthInterface::class, function (Container $c) {
            return new BasicAuth($c->get(Database::class));
        });

        // Module Registry
        $this->container->singleton(ModuleRegistry::class, function () {
            return new ModuleRegistry();
        });

        // Trash Manager
        $this->container->singleton(TrashManager::class, function (Container $c) {
            return new TrashManager(
                $c->get(Database::class),
                $this->config->get('trash.retention_days', 30)
            );
        });
    }

    public function run(): void
    {
        // Check if this is a Web UI request first
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        if (str_starts_with($uri, '/ui')) {
            $this->handleWebUI();
            return;
        }

        // Handle WebDAV requests
        $this->server = new Server($this->container, $this->config);
        $this->server->initialize();
        $this->server->run();
    }

    private function handleWebUI(): void
    {
        // Initialize modules
        /** @var ModuleRegistry $registry */
        $registry = $this->container->get(ModuleRegistry::class);

        $loader = new ModuleLoader(
            $this->config->get('app.base_path', getcwd()),
            $this->container
        );

        foreach ($loader->loadAll() as $module) {
            $registry->register($module);
        }

        // Create root collection
        /** @var TrashManager $trashManager */
        $trashManager = $this->container->get(TrashManager::class);
        $trash = new TrashCollection($trashManager);
        $root = new RootCollection($registry, $trash);

        // Handle Web UI request
        $handler = new WebUIHandler(
            $this->container,
            $registry,
            $trashManager,
            $root
        );

        $handler->handle();
    }

    public function getContainer(): Container
    {
        return $this->container;
    }

    public function getConfig(): Config
    {
        return $this->config;
    }

    /**
     * Create a default admin user if no users exist
     */
    public function ensureAdminUser(string $username = 'admin', string $password = 'admin'): void
    {
        /** @var AuthInterface $auth */
        $auth = $this->container->get(AuthInterface::class);

        if (!$auth->userExists($username)) {
            $auth->createUser($username, $password);
        }
    }
}
