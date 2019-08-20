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
        $zk = new ZkExt();
        $className = array_reverse(explode('\\', static::class))[0];
        $zk->connect('localhost:2181');
        $zk->create(
            '/' . $className,
            '1',
            [["perms" => ZkExt::PERM_ALL, "scheme" => "world", "id" => "anyone"]]
        );
        $zk->close();
        $zk = new ZkExt();
        $zk->connect('localhost:2181/' . $className);
        static::$zk = $zk;
    }

}
