<?php

declare(strict_types=1);

namespace ListDAV\Module;

class ModuleCapabilities
{
    /**
     * @param array<string> $customProperties
     * @param array<string> $acceptsFrom
     * @param array<string> $fileFormats
     */
    public function __construct(
        public readonly bool $read = true,
        public readonly bool $write = false,
        public readonly bool $delete = false,
        public readonly bool $createFolders = false,
        public readonly array $customProperties = [],
        public readonly array $acceptsFrom = [],
        public readonly array $fileFormats = ['json'],
        public readonly string $defaultFormat = 'json',
    ) {}
}
