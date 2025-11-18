<?php

declare(strict_types=1);

namespace ListDAV\Tests\Unit\Core;

use ListDAV\Core\Config;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/listdav_test_' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tempDir)) {
            rmdir($this->tempDir);
        }
    }

    public function testDefaultValues(): void
    {
        $config = new Config($this->tempDir);

        $this->assertEquals('development', $config->get('app.env'));
        $this->assertEquals('ListDAV', $config->get('auth.realm'));
        $this->assertEquals(30, $config->get('trash.retention_days'));
    }

    public function testGetNestedValue(): void
    {
        $config = new Config($this->tempDir);

        $this->assertIsString($config->get('database.path'));
        $this->assertIsInt($config->get('cache.ttl'));
    }

    public function testGetWithDefault(): void
    {
        $config = new Config($this->tempDir);

        $this->assertEquals('default', $config->get('nonexistent.key', 'default'));
        $this->assertNull($config->get('nonexistent.key'));
    }

    public function testSetValue(): void
    {
        $config = new Config($this->tempDir);

        $config->set('custom.setting', 'value');
        $this->assertEquals('value', $config->get('custom.setting'));
    }

    public function testSetNestedValue(): void
    {
        $config = new Config($this->tempDir);

        $config->set('deep.nested.value', 123);
        $this->assertEquals(123, $config->get('deep.nested.value'));
    }

    public function testAllReturnsArray(): void
    {
        $config = new Config($this->tempDir);

        $all = $config->all();
        $this->assertIsArray($all);
        $this->assertArrayHasKey('app', $all);
        $this->assertArrayHasKey('auth', $all);
    }
}
