<?php

declare(strict_types=1);

namespace ListDAV\Tests\Unit\Module;

use ListDAV\Core\Container;
use ListDAV\Module\AbstractModule;
use ListDAV\Module\ModuleCapabilities;
use ListDAV\Module\ModuleRegistry;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Sabre\DAV\ICollection;
use Sabre\DAV\SimpleCollection;

class ModuleRegistryTest extends TestCase
{
    private ModuleRegistry $registry;

    protected function setUp(): void
    {
        $this->registry = new ModuleRegistry();
    }

    public function testRegisterAndGet(): void
    {
        $module = $this->createMockModule('test');
        $this->registry->register($module);

        $retrieved = $this->registry->get('test');
        $this->assertSame($module, $retrieved);
    }

    public function testRegisterDuplicateThrows(): void
    {
        $module = $this->createMockModule('test');
        $this->registry->register($module);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module already registered: test');

        $this->registry->register($module);
    }

    public function testGetNonexistentThrows(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module not found: nonexistent');

        $this->registry->get('nonexistent');
    }

    public function testHas(): void
    {
        $this->assertFalse($this->registry->has('test'));

        $module = $this->createMockModule('test');
        $this->registry->register($module);

        $this->assertTrue($this->registry->has('test'));
    }

    public function testAll(): void
    {
        $module1 = $this->createMockModule('mod1');
        $module2 = $this->createMockModule('mod2');

        $this->registry->register($module1);
        $this->registry->register($module2);

        $all = $this->registry->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('mod1', $all);
        $this->assertArrayHasKey('mod2', $all);
    }

    public function testGetModuleForPath(): void
    {
        $module = $this->createMockModule('youtube');
        $this->registry->register($module);

        $found = $this->registry->getModuleForPath('/youtube/playlists/Music');
        $this->assertSame($module, $found);

        $notFound = $this->registry->getModuleForPath('/other/path');
        $this->assertNull($notFound);
    }

    private function createMockModule(string $name): AbstractModule
    {
        return new class ($name) extends AbstractModule {
            public function __construct(private readonly string $moduleName)
            {
            }

            public function getName(): string
            {
                return $this->moduleName;
            }

            public function getVersion(): string
            {
                return '1.0.0';
            }

            public function getDescription(): string
            {
                return 'Test module';
            }

            public function getRootCollection(): ICollection
            {
                return new SimpleCollection($this->moduleName);
            }

            public function getCapabilities(): ModuleCapabilities
            {
                return new ModuleCapabilities();
            }
        };
    }
}
