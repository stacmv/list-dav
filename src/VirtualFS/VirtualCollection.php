<?php

declare(strict_types=1);

namespace ListDAV\VirtualFS;

use Sabre\DAV\Collection;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\INode;

abstract class VirtualCollection extends Collection
{
    protected string $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return array<INode>
     */
    abstract public function getChildren(): array;

    public function getChild($name): INode
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) {
                return $child;
            }
        }

        throw new NotFound("File not found: {$name}");
    }

    public function childExists($name): bool
    {
        foreach ($this->getChildren() as $child) {
            if ($child->getName() === $name) {
                return true;
            }
        }
        return false;
    }
}
