<?php

declare(strict_types=1);

namespace ListDAV\Module;

use ListDAV\Core\Container;
use Sabre\DAV\ICollection;
use Sabre\DAV\INode;

interface ModuleInterface
{
    public function getName(): string;

    public function getVersion(): string;

    public function getDescription(): string;

    public function getRootCollection(): ICollection;

    /**
     * @return array<string, ICollection>
     */
    public function getViewCollections(): array;

    public function getCapabilities(): ModuleCapabilities;

    /**
     * @return array<string, mixed>
     */
    public function getConfigSchema(): array;

    public function canAcceptFrom(string $sourceModule): bool;

    public function convertFromExternal(string $sourceModule, INode $node): ?INode;

    public function initialize(Container $container): void;

    public function shutdown(): void;
}
