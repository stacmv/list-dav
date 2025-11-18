<?php

declare(strict_types=1);

namespace ListDAV\Module;

use RuntimeException;

class ModuleRegistry
{
    /** @var array<string, ModuleInterface> */
    private array $modules = [];

    public function register(ModuleInterface $module): void
    {
        $name = $module->getName();
        if (isset($this->modules[$name])) {
            throw new RuntimeException("Module already registered: {$name}");
        }
        $this->modules[$name] = $module;
    }

    public function get(string $name): ModuleInterface
    {
        if (!isset($this->modules[$name])) {
            throw new RuntimeException("Module not found: {$name}");
        }
        return $this->modules[$name];
    }

    public function has(string $name): bool
    {
        return isset($this->modules[$name]);
    }

    /**
     * @return array<string, ModuleInterface>
     */
    public function all(): array
    {
        return $this->modules;
    }

    public function getModuleForPath(string $path): ?ModuleInterface
    {
        $parts = explode('/', trim($path, '/'));
        if (empty($parts[0])) {
            return null;
        }

        $moduleName = $parts[0];
        return $this->modules[$moduleName] ?? null;
    }
}
