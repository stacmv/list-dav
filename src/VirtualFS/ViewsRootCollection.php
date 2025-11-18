<?php

declare(strict_types=1);

namespace ListDAV\VirtualFS;

use ListDAV\Module\ModuleRegistry;
use Sabre\DAV\INode;

class ViewsRootCollection extends VirtualCollection
{
    public function __construct(
        private readonly ModuleRegistry $registry,
    ) {
        parent::__construct('views');
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $children = [];

        foreach ($this->registry->all() as $module) {
            foreach ($module->getViewCollections() as $name => $collection) {
                $children[] = $collection;
            }
        }

        return $children;
    }
}
