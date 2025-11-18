<?php

declare(strict_types=1);

namespace ListDAV\Module;

use ListDAV\Core\Container;
use Sabre\DAV\INode;

abstract class AbstractModule implements ModuleInterface
{
    protected Container $container;

    public function initialize(Container $container): void
    {
        $this->container = $container;
    }

    public function shutdown(): void
    {
        // Override in subclass if needed
    }

    /**
     * @return array<string, \Sabre\DAV\ICollection>
     */
    public function getViewCollections(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    public function getConfigSchema(): array
    {
        return [];
    }

    public function canAcceptFrom(string $sourceModule): bool
    {
        return in_array($sourceModule, $this->getCapabilities()->acceptsFrom, true);
    }

    public function convertFromExternal(string $sourceModule, INode $node): ?INode
    {
        return null;
    }
}
