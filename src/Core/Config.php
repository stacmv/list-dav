<?php

declare(strict_types=1);

namespace ListDAV\Core;

use Dotenv\Dotenv;

class Config
{
    /** @var array<string, mixed> */
    private array $config = [];

    public function __construct(string $basePath)
    {
        $this->loadEnvironment($basePath);
        $this->loadDefaults();
    }

    private function loadEnvironment(string $basePath): void
    {
        if (file_exists($basePath . '/.env')) {
            $dotenv = Dotenv::createImmutable($basePath);
            $dotenv->load();
        }
    }

    private function loadDefaults(): void
    {
        $this->config = [
            'app' => [
                'env' => $_ENV['APP_ENV'] ?? 'development',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? true, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost:8080',
            ],
            'auth' => [
                'driver' => $_ENV['AUTH_DRIVER'] ?? 'basic',
                'realm' => $_ENV['AUTH_REALM'] ?? 'ListDAV',
            ],
            'database' => [
                'path' => $_ENV['DB_PATH'] ?? 'var/data/listdav.sqlite',
            ],
            'cache' => [
                'ttl' => (int) ($_ENV['CACHE_TTL'] ?? 300),
            ],
            'trash' => [
                'retention_days' => (int) ($_ENV['TRASH_RETENTION_DAYS'] ?? 30),
            ],
            'youtube' => [
                'client_id' => $_ENV['YOUTUBE_CLIENT_ID'] ?? '',
                'client_secret' => $_ENV['YOUTUBE_CLIENT_SECRET'] ?? '',
                'redirect_uri' => $_ENV['YOUTUBE_REDIRECT_URI'] ?? '',
            ],
        ];
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $value = $this->config;

        foreach ($keys as $k) {
            if (!is_array($value) || !array_key_exists($k, $value)) {
                return $default;
            }
            $value = $value[$k];
        }

        return $value;
    }

    public function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $config = &$this->config;

        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->config;
    }
}
