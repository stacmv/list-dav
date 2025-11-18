<?php

declare(strict_types=1);

namespace ListDAV\Auth;

interface AuthInterface
{
    public function authenticate(string $username, string $password): ?User;

    /**
     * @return array<User>
     */
    public function getUsers(): array;

    public function createUser(string $username, string $password): User;

    public function deleteUser(string $username): void;

    public function userExists(string $username): bool;
}
