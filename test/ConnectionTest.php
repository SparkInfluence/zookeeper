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
        $mock = \Mockery::mock('Zookeeper');
        $mock->shouldReceive('connect');
        $mock->shouldReceive('getState')->andReturn(\Zookeeper::CONNECTING_STATE);
        $zookeeper = new Zookeeper($mock);
        $this->expectException(ConnectionError::class);
        $this->expectExceptionMessage('Could not connect to zookeeper server');
        $zookeeper->connect('localhost:2181');
    }

}
