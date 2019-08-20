<?php

namespace SparkInfluence\Zookeeper\Tests;

use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Zookeeper;

class ConnectionTest extends TestCase
{

    public function testConnectionStaticMethod()
    {
        $zookeeper = Zookeeper::connection('localhost:2181');
        $this->assertNotEmpty($zookeeper);
    }

}
