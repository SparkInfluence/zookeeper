<?php

namespace SparkInfluence\Zookeeper\Tests;

use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Zookeeper;

class ZookeeperTest extends TestCase
{

    use ZkTrait;

    public function testExists()
    {
        $zookeeper = new Zookeeper(static::$zk);
        $this->assertFalse($zookeeper->exists('/testNode'));
        $zookeeper->create('/testNode', '');
        $this->assertTrue($zookeeper->exists('/testNode'));
        $zookeeper->remove('/testNode');
    }

}
