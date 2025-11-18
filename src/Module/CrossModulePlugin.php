<?php

declare(strict_types=1);

namespace ListDAV\Module;

use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;

class CrossModulePlugin extends ServerPlugin
{
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {}

    public function initialize(Server $server): void
    {
        $server->on('beforeMove', [$this, 'beforeMove'], 10);
        $server->on('beforeCopy', [$this, 'beforeCopy'], 10);
    }

    /**
     * @return bool|null
     */
    public function beforeMove(string $source, string $destination): ?bool
    {
        $this->checkCrossModuleOperation($source, $destination, 'move');
        return null;
    }

    /**
     * @return bool|null
     */
    public function beforeCopy(string $source, string $destination): ?bool
    {
        $this->checkCrossModuleOperation($source, $destination, 'copy');
        return null;
    }

    private function checkCrossModuleOperation(string $source, string $destination, string $operation): void
    {
        $sourceModule = $this->registry->getModuleForPath($source);
        $destModule = $this->registry->getModuleForPath($destination);

        if ($sourceModule === null || $destModule === null) {
            return;
        }

        if ($sourceModule->getName() === $destModule->getName()) {
            return;
        }

        if (!$destModule->canAcceptFrom($sourceModule->getName())) {
            throw new Forbidden(sprintf(
                'Cannot %s from %s to %s: modules are not compatible',
                $operation,
                $sourceModule->getName(),
                $destModule->getName()
            ));
        }
    }

    public function getPluginName(): string
    {
        return 'cross-module';
    }
}
