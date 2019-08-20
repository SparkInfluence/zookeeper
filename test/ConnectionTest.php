<?php

namespace SparkInfluence\Zookeeper\Tests;

use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Exception\ConnectionError;
use SparkInfluence\Zookeeper\Zookeeper;

class ConnectionTest extends TestCase
{

    public function testConnectionStaticMethod()
    {
        $zookeeper = Zookeeper::connection('localhost:2181');
        $this->assertNotEmpty($zookeeper);
    }

    public function testConnectionError()
    {
        $this->expectException(ConnectionError::class);
        $this->expectExceptionMessage('Could not connect to zookeeper server');
        Zookeeper::connection('dev.sparkinfluence.net:80');
    }

    public function testConstructorCatchesBasePathErrors()
    {
        $zk = new \Zookeeper();
        $z = new Zookeeper($zk, '/test/zookeeper');
        $p = new \ReflectionProperty($z, 'basePath');
        $p->setAccessible(true);
        $this->assertEquals('', $p->getValue($z));
        $z = new Zookeeper($zk, '/test/valid');
        $this->assertEquals('/test/valid', $p->getValue($z));
    }

}
