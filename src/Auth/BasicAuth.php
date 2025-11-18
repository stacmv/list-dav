<?php

declare(strict_types=1);

namespace ListDAV\Auth;

use ListDAV\Core\Database;

class BasicAuth implements AuthInterface
{
    public function __construct(
        private readonly Database $database,
    ) {}

    public function authenticate(string $username, string $password): ?User
    {
        $result = $this->database->query(
            'SELECT * FROM users WHERE username = :username',
            ['username' => $username]
        );

        if (empty($result)) {
            return null;
        }

        $row = $result[0];

        if (!password_verify($password, $row['password_hash'])) {
            return null;
        }

        return new User(
            (int) $row['id'],
            $row['username'],
            $row['created_at'],
        );
    }

    /**
     * @return array<User>
     */
    public function getUsers(): array
    {
        $rows = $this->database->query('SELECT * FROM users ORDER BY username');

        return array_map(
            fn(array $row) => new User(
                (int) $row['id'],
                $row['username'],
                $row['created_at'],
            ),
            $rows
        );
    }

    public function createUser(string $username, string $password): User
    {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        $this->database->execute(
            'INSERT INTO users (username, password_hash) VALUES (:username, :hash)',
            ['username' => $username, 'hash' => $hash]
        );

        $id = (int) $this->database->lastInsertId();

        return new User($id, $username, date('Y-m-d H:i:s'));
    }

    public function deleteUser(string $username): void
    {
        $this->database->execute(
            'DELETE FROM users WHERE username = :username',
            ['username' => $username]
        );
    }

    public function userExists(string $username): bool
    {
        $result = $this->database->query(
            'SELECT 1 FROM users WHERE username = :username',
            ['username' => $username]
        );

        return !empty($result);
    }
}
