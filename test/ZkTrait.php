<?php

namespace SparkInfluence\Zookeeper\Tests;

use SparkInfluence\Zookeeper\Zookeeper;
use Zookeeper as ZkExt;

trait ZkTrait
{

    /** @var ZkExt */
    private static $zk;

    /** @var Zookeeper */
    private $zookeeper;

    /**
     * @before
     */
    public function initializeZookeeperLibrary()
    {
        $this->zookeeper = new Zookeeper(static::$zk);
    }

    /**
     * @beforeClass
     */
    public static function initializeZookeeper()
    {
        static::$zk = new ZkExt();
        static::$zk->connect('localhost:2181');
    }

}
