<?php

namespace SparkInfluence\Zookeeper\Tests;

use PHPUnit\Framework\TestCase;
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

}
