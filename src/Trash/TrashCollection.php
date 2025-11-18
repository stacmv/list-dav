<?php

declare(strict_types=1);

namespace ListDAV\Trash;

use ListDAV\VirtualFS\VirtualCollection;
use Sabre\DAV\INode;

class TrashCollection extends VirtualCollection
{
    public function __construct(
        private readonly TrashManager $manager,
    ) {
        parent::__construct('trash');
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $items = $this->manager->list();
        return array_map(
            fn(array $item) => new TrashItem($item, $this->manager),
            $items
        );
    }
}
