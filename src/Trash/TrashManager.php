<?php

declare(strict_types=1);

namespace ListDAV\Trash;

use ListDAV\Core\Database;

class TrashManager
{
    public function __construct(
        private readonly Database $database,
        private readonly int $retentionDays = 30,
    ) {}

    /**
     * @param array<string, mixed> $metadata
     */
    public function trash(string $path, string $module, array $metadata = []): int
    {
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->retentionDays} days"));

        $this->database->execute(
            'INSERT INTO trash (original_path, module, metadata, expires_at) VALUES (:path, :module, :metadata, :expires)',
            [
                'path' => $path,
                'module' => $module,
                'metadata' => json_encode($metadata),
                'expires' => $expiresAt,
            ]
        );

        return (int) $this->database->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?string $module = null): array
    {
        if ($module !== null) {
            return $this->database->query(
                'SELECT * FROM trash WHERE module = :module ORDER BY deleted_at DESC',
                ['module' => $module]
            );
        }

        return $this->database->query('SELECT * FROM trash ORDER BY deleted_at DESC');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function get(int $id): ?array
    {
        $result = $this->database->query(
            'SELECT * FROM trash WHERE id = :id',
            ['id' => $id]
        );

        return $result[0] ?? null;
    }

    public function restore(int $id): bool
    {
        $item = $this->get($id);
        if ($item === null) {
            return false;
        }

        $this->database->execute(
            'DELETE FROM trash WHERE id = :id',
            ['id' => $id]
        );

        return true;
    }

    public function delete(int $id): void
    {
        $this->database->execute(
            'DELETE FROM trash WHERE id = :id',
            ['id' => $id]
        );
    }

    public function cleanup(): int
    {
        return $this->database->execute(
            'DELETE FROM trash WHERE expires_at < datetime(\'now\')'
        );
    }
}
