<?php

declare(strict_types=1);

namespace ListDAV\VirtualFS;

use Sabre\DAV\File;

abstract class VirtualFile extends File
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

    abstract public function get(): mixed;

    abstract public function getSize(): int;

    public function getContentType(): ?string
    {
        $ext = pathinfo($this->name, PATHINFO_EXTENSION);

        return match ($ext) {
            'json' => 'application/json',
            'txt' => 'text/plain',
            'md' => 'text/markdown',
            'url' => 'application/x-url',
            'webloc' => 'application/x-webloc',
            default => 'application/octet-stream',
        };
    }

    public function getETag(): ?string
    {
        return '"' . md5($this->name . $this->getSize()) . '"';
    }
}
