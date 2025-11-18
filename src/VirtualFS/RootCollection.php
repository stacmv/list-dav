<?php

declare(strict_types=1);

namespace ListDAV\VirtualFS;

use ListDAV\Module\ModuleRegistry;
use ListDAV\Trash\TrashCollection;
use Sabre\DAV\INode;

class RootCollection extends VirtualCollection
{
    public function __construct(
        private readonly ModuleRegistry $registry,
        private readonly TrashCollection $trash,
    ) {
        parent::__construct('');
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $children = [];

        // Add module root collections
        foreach ($this->registry->all() as $module) {
            $children[] = $module->getRootCollection();
        }

        // Add views collection
        $children[] = new ViewsRootCollection($this->registry);

        // Add trash
        $children[] = $this->trash;

        return $children;
    }
}
