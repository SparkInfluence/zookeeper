<?php

namespace SparkInfluence\Zookeeper\Tests;

trait ZkTrait
{

    private static $zk;

    /**
     * @beforeClass
     */
    public static function initializeZookeeper()
    {
        static::$zk = new \Zookeeper();
        static::$zk->connect('localhost:2181');
    }

}
