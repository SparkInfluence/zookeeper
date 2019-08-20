<?php

namespace SparkInfluence\Zookeeper\Tests;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Exception\Exception;
use SparkInfluence\Zookeeper\Exception\NodeError;
use SparkInfluence\Zookeeper\Zookeeper;
use ZookeeperException;

class ZookeeperTest extends TestCase
{

    use ZkTrait, MockeryPHPUnitIntegration;

    public function testCreate()
    {
        $this->assertFalse($this->zookeeper->exists('/testCreate'));
        $this->zookeeper->create('/testCreate', 'This is a test');
        $this->assertTrue($this->zookeeper->exists('/testCreate'));
        $this->assertEquals('This is a test', $this->zookeeper->get('/testCreate'));
    }

    public function testCreateProxiesExceptions()
    {
        $ext = Mockery::mock('Zookeeper');
        $exception = new ZookeeperException();
        $ext->shouldReceive('create')->andThrow($exception);
        $zk = new Zookeeper($ext);
        $this->expectException(Exception::class);
        $zk->create('/whatever', '');
    }

    public function testGet()
    {
        $this->zookeeper->create('/testGet', 'Lorem Ipsum');
        $this->assertEquals('Lorem Ipsum', $this->zookeeper->get('/testGet'));
    }

    public function testGetWatcher()
    {
        $this->zookeeper->ensurePath('/testGet/path');
        $ranListener = false;
        $this->zookeeper->get('/testGet', function ($eventType, $_, $path) use (&$ranListener) {
            $ranListener = true;
            $this->assertEquals($eventType, \Zookeeper::CHANGED_EVENT);
            $this->assertEquals($path, '/testGet');
        });
        $this->zookeeper->set('/testGet', '2');
        zookeeper_dispatch();
        $this->assertTrue($ranListener);
    }

    public function testGetThrowsErrorOnFalse()
    {
        $ext = Mockery::mock('Zookeeper');
        $ext->shouldReceive('get')->andReturn(false);
        $zk = new Zookeeper($ext);
        $this->expectException(NodeError::class);
        $zk->get('/qwerty');
    }

    public function testSet()
    {
        $this->zookeeper->create('/testSet', 'Foobar');
        $this->assertTrue($this->zookeeper->set('/testSet', 'Bazbat'));
        $this->assertEquals('Bazbat', $this->zookeeper->get('/testSet'));
    }

    public function testGetChildren()
    {
        $this->zookeeper->ensurePath('/testGetChildren/1');
        $this->assertEquals([], $this->zookeeper->getChildren('/testGetChildren'));
        $children = ['foo', 'bar', 'baz', 'bat', 'qwerty', 'bim', 'bam'];
        foreach ($children as $child) {
            $this->zookeeper->create("/testGetChildren/$child", '1');
        }
        $this->assertEqualsCanonicalizing($children, $this->zookeeper->getChildren('/testGetChildren'));
    }

    public function testGetChildrenWatcherOnRemove()
    {
        $this->zookeeper->ensurePath('/testGetChildren/watcher/1');
        $ranListener = false;
        $this->zookeeper->create('/testGetChildren/watcher/node', '1');
        $this->zookeeper->getChildren('/testGetChildren/watcher', function ($type) use (&$ranListener) {
            $ranListener = true;
            $this->assertEquals(\Zookeeper::CHILD_EVENT, $type);
        });
        $this->zookeeper->remove('/testGetChildren/watcher/node');
        zookeeper_dispatch();
        $this->assertTrue($ranListener);
    }

    public function testGetChildrenWatcherOnCreate()
    {
        $this->zookeeper->ensurePath('/testGetChildren/watcher2/1');
        $ranListener = false;
        $this->zookeeper->create('/testGetChildren/watcher2/node', '1');
        $this->zookeeper->getChildren('/testGetChildren/watcher2', function ($type) use (&$ranListener) {
            $ranListener = true;
            $this->assertEquals(\Zookeeper::CHILD_EVENT, $type);
        });
        $this->zookeeper->create('/testGetChildren/watcher2/node2', '1');
        zookeeper_dispatch();
        $this->assertTrue($ranListener);
    }

    public function testGetChildrenThrowsErrorOnFalse()
    {
        $ext = Mockery::mock('Zookeeper');
        $ext->shouldReceive('getChildren')->andReturn(false);
        $zk = new Zookeeper($ext);
        $this->expectException(NodeError::class);
        $zk->getChildren('/qwerty');
    }

    public function testExists()
    {
        $this->assertFalse($this->zookeeper->exists('/testNode'));
        $this->zookeeper->create('/testNode', '');
        $this->assertTrue($this->zookeeper->exists('/testNode'));
        $this->zookeeper->remove('/testNode');
    }

    public function testExistsWatcher()
    {
        $this->zookeeper->ensurePath('/testExists/watcher/node');
        $ranListener = false;
        $this->zookeeper->exists('/testExists/watcher', function ($type, $_, $path) use (&$ranListener) {
            $ranListener = true;
            $this->assertEquals('/testExists/watcher', $path);
            $this->assertEquals(\Zookeeper::CHANGED_EVENT, $type);
        });
        $this->zookeeper->set('/testExists/watcher', '2');
        zookeeper_dispatch();
        $this->assertTrue($ranListener);
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
