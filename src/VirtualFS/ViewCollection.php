<?php

declare(strict_types=1);

namespace ListDAV\VirtualFS;

use Closure;
use Sabre\DAV\INode;

class ViewCollection extends VirtualCollection
{
    /** @var Closure(INode): bool */
    private Closure $filter;

    /** @var Closure(): array<INode> */
    private Closure $sourceProvider;

    /**
     * @param Closure(): array<INode> $sourceProvider
     * @param Closure(INode): bool $filter
     */
    public function __construct(
        string $name,
        Closure $sourceProvider,
        Closure $filter,
    ) {
        parent::__construct($name);
        $this->sourceProvider = $sourceProvider;
        $this->filter = $filter;
    }

    /**
     * @return array<INode>
     */
    public function getChildren(): array
    {
        $source = ($this->sourceProvider)();
        return array_values(array_filter($source, $this->filter));
    }
}
