<?php

declare(strict_types=1);

namespace ListDAV\Trash;

use ListDAV\VirtualFS\VirtualFile;

class TrashItem extends VirtualFile
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(
        private readonly array $data,
        private readonly TrashManager $manager,
    ) {
        $name = sprintf(
            '%d_%s_%s',
            $data['id'],
            $data['module'],
            basename($data['original_path'])
        );
        parent::__construct($name);
    }

    public function get(): string
    {
        return json_encode([
            'id' => $this->data['id'],
            'original_path' => $this->data['original_path'],
            'module' => $this->data['module'],
            'metadata' => json_decode($this->data['metadata'] ?? '{}', true),
            'deleted_at' => $this->data['deleted_at'],
            'expires_at' => $this->data['expires_at'],
        ], JSON_PRETTY_PRINT);
    }

    public function getSize(): int
    {
        return strlen($this->get());
    }

    public function getContentType(): ?string
    {
        return 'application/json';
    }

    public function delete(): void
    {
        $this->manager->delete((int) $this->data['id']);
    }
}
