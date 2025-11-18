<?php

declare(strict_types=1);

namespace ListDAV\Tests\Unit\Auth;

use ListDAV\Auth\BasicAuth;
use ListDAV\Core\Database;
use PHPUnit\Framework\TestCase;

class BasicAuthTest extends TestCase
{
    private Database $database;
    private BasicAuth $auth;
    private string $dbPath;

    protected function setUp(): void
    {
        $this->dbPath = sys_get_temp_dir() . '/listdav_test_' . uniqid() . '.sqlite';
        $this->database = new Database($this->dbPath);
        $this->auth = new BasicAuth($this->database);
    }

    protected function tearDown(): void
    {
        unset($this->database);
        if (file_exists($this->dbPath)) {
            unlink($this->dbPath);
        }
    }

    public function testCreateUser(): void
    {
        $user = $this->auth->createUser('testuser', 'testpass');

        $this->assertEquals('testuser', $user->username);
        $this->assertIsInt($user->id);
    }

    public function testAuthenticateSuccess(): void
    {
        $this->auth->createUser('testuser', 'testpass');

        $user = $this->auth->authenticate('testuser', 'testpass');

        $this->assertNotNull($user);
        $this->assertEquals('testuser', $user->username);
    }

    public function testAuthenticateWrongPassword(): void
    {
        $this->auth->createUser('testuser', 'testpass');

        $user = $this->auth->authenticate('testuser', 'wrongpass');

        $this->assertNull($user);
    }

    public function testAuthenticateNonexistentUser(): void
    {
        $user = $this->auth->authenticate('nonexistent', 'pass');

        $this->assertNull($user);
    }

    public function testUserExists(): void
    {
        $this->assertFalse($this->auth->userExists('testuser'));

        $this->auth->createUser('testuser', 'testpass');

        $this->assertTrue($this->auth->userExists('testuser'));
    }

    public function testDeleteUser(): void
    {
        $this->auth->createUser('testuser', 'testpass');
        $this->assertTrue($this->auth->userExists('testuser'));

        $this->auth->deleteUser('testuser');

        $this->assertFalse($this->auth->userExists('testuser'));
    }

    public function testGetUsers(): void
    {
        $this->auth->createUser('user1', 'pass1');
        $this->auth->createUser('user2', 'pass2');

        $users = $this->auth->getUsers();

        $this->assertCount(2, $users);
        $this->assertEquals('user1', $users[0]->username);
        $this->assertEquals('user2', $users[1]->username);
    }
}
