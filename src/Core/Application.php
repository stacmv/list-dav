<?php

declare(strict_types=1);

namespace ListDAV\Core;

use ListDAV\Auth\AuthInterface;
use ListDAV\Auth\BasicAuth;

class Application
{
    private Container $container;
    private Config $config;

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
    }

    public function run(): void
    {
        $server = new Server($this->container, $this->config);
        $server->initialize();
        $server->run();
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
