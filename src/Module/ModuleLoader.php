<?php

declare(strict_types=1);

namespace ListDAV\Module;

use ListDAV\Core\Container;

class ModuleLoader
{
    public function __construct(
        private readonly string $basePath,
        private readonly Container $container,
    ) {}

    /**
     * @return array<ModuleInterface>
     */
    public function loadAll(): array
    {
        $modules = array_merge(
            $this->loadComposerModules(),
            $this->loadLocalModules()
        );

        foreach ($modules as $module) {
            $module->initialize($this->container);
        }

        return $modules;
    }

    /**
     * @return array<ModuleInterface>
     */
    private function loadComposerModules(): array
    {
        $modules = [];
        $installedPath = $this->basePath . '/vendor/composer/installed.json';

        if (!file_exists($installedPath)) {
            return $modules;
        }

        $installed = json_decode(file_get_contents($installedPath), true);
        $packages = $installed['packages'] ?? $installed;

        foreach ($packages as $package) {
            if (isset($package['extra']['listdav-module'])) {
                $class = $package['extra']['listdav-module']['class'];
                if (class_exists($class)) {
                    $modules[] = new $class();
                }
            }
        }

        return $modules;
    }

    /**
     * @return array<ModuleInterface>
     */
    private function loadLocalModules(): array
    {
        $modules = [];
        $modulesPath = $this->basePath . '/modules';

        if (!is_dir($modulesPath)) {
            return $modules;
        }

        $dirs = glob($modulesPath . '/*/module.php');
        if ($dirs === false) {
            return $modules;
        }

        foreach ($dirs as $file) {
            $module = require $file;
            if ($module instanceof ModuleInterface) {
                $modules[] = $module;
            }
        }

        return $modules;
    }
}
