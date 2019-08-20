<?php

namespace SparkInfluence\Zookeeper\Tests;

use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Exception\Exception;
use SparkInfluence\Zookeeper\Zookeeper;

class ZookeeperTest extends TestCase
{

    use ZkTrait;

    /** @var Zookeeper */
    private $zookeeper;

    /**
     * @before
     */
    public function init()
    {
        $this->zookeeper = new Zookeeper(static::$zk);
    }

    public function testExists()
    {
        $this->assertFalse($this->zookeeper->exists('/testNode'));
        $this->zookeeper->create('/testNode', '');
        $this->assertTrue($this->zookeeper->exists('/testNode'));
        $this->zookeeper->remove('/testNode');
    }

    public function testRemove()
    {
        $this->zookeeper->create('/foobar', '');
        $this->assertTrue($this->zookeeper->exists('/foobar'));
        $this->zookeeper->remove('/foobar');
        $this->assertFalse($this->zookeeper->exists('/foobar'));
    }

    public function testEnsurePath()
    {
        $this->assertFalse($this->zookeeper->exists('/test'));
        $this->assertFalse($this->zookeeper->exists('/test/ensure'));
        $this->assertFalse($this->zookeeper->exists('/test/ensure/path'));
        $this->zookeeper->ensurePath('/test/ensure/path/to');
        $this->assertTrue($this->zookeeper->exists('/test'));
        $this->assertTrue($this->zookeeper->exists('/test/ensure'));
        $this->assertTrue($this->zookeeper->exists('/test/ensure/path'));
        $this->assertFalse($this->zookeeper->exists('/test/ensure/path/to'));
    }

    public function testEnsurePathDoesNotThrowExceptions()
    {
        $this->assertFalse($this->zookeeper->ensurePath('/test/zookeeper/fail'));
    }

    /**
     * @dataProvider getInvalidNodePathParts
     */
    public function testInvalidNodePathParts($part)
    {
        $path = "test/$part/node";
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("$path is an invalid path!");
        $this->zookeeper->exists($path);
    }

    public function getInvalidNodePathParts()
    {
        return [
            ['.'],
            ['..'],
            ['zookeeper'],
        ];
    }

}
