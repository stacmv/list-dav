<?php

declare(strict_types=1);

namespace ListDAV\Tests\Unit\Core;

use ListDAV\Core\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use stdClass;

class ContainerTest extends TestCase
{
    public function testBindAndGet(): void
    {
        $container = new Container();

        $obj = new stdClass();
        $container->bind('test', $obj);

        $this->assertSame($obj, $container->get('test'));
    }

    public function testBindClosure(): void
    {
        $container = new Container();

        $container->bind('test', fn() => new stdClass());

        $result = $container->get('test');
        $this->assertInstanceOf(stdClass::class, $result);
    }

    public function testSingletonReturnsSameInstance(): void
    {
        $container = new Container();

        $container->singleton('test', fn() => new stdClass());

        $first = $container->get('test');
        $second = $container->get('test');

        $this->assertSame($first, $second);
    }

    public function testInstance(): void
    {
        $container = new Container();

        $obj = new stdClass();
        $container->instance('test', $obj);

        $this->assertSame($obj, $container->get('test'));
    }

    public function testHas(): void
    {
        $container = new Container();

        $this->assertFalse($container->has('test'));

        $container->bind('test', new stdClass());

        $this->assertTrue($container->has('test'));
    }

    public function testGetThrowsForUnbound(): void
    {
        $container = new Container();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No binding found for: nonexistent');

        $container->get('nonexistent');
    }

    public function testClosureReceivesContainer(): void
    {
        $container = new Container();

        $container->bind('dep', new stdClass());
        $container->bind('service', function (Container $c) {
            return $c->get('dep');
        });

        $result = $container->get('service');
        $this->assertInstanceOf(stdClass::class, $result);
    }
}
