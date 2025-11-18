<?php

declare(strict_types=1);

namespace ListDAV\WebUI\Controllers;

use ListDAV\Core\Config;
use ListDAV\Core\Container;
use ListDAV\Module\ModuleRegistry;

class UIController
{
    public function __construct(
        private readonly Container $container,
        private readonly Config $config,
        private readonly ModuleRegistry $registry,
    ) {}

    public function index(): string
    {
        return file_get_contents(__DIR__ . '/../Views/index.html');
    }

    /**
     * @return array<string, mixed>
     */
    public function browse(string $path): array
    {
        $path = '/' . trim($path, '/');
        $items = [];

        // This would integrate with the WebDAV server to list contents
        // For now, return a simplified structure

        return [
            'path' => $path,
            'items' => $items,
        ];
    }

    /**
     * @param array<string> $sources
     */
    public function copy(array $sources, string $destination): bool
    {
        // Implement copy logic
        return true;
    }

    /**
     * @param array<string> $sources
     */
    public function move(array $sources, string $destination): bool
    {
        // Implement move logic
        return true;
    }

    /**
     * @param array<string> $paths
     */
    public function delete(array $paths): bool
    {
        // Implement delete logic
        return true;
    }

    public function rename(string $path, string $newName): bool
    {
        // Implement rename logic
        return true;
    }

    public function createFolder(string $path, string $name): bool
    {
        // Implement create folder logic
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        $modules = [];
        foreach ($this->registry->all() as $module) {
            $modules[$module->getName()] = [
                'name' => $module->getName(),
                'version' => $module->getVersion(),
                'description' => $module->getDescription(),
                'configSchema' => $module->getConfigSchema(),
            ];
        }

        return [
            'modules' => $modules,
        ];
    }
}
