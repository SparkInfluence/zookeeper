<?php

namespace SparkInfluence\Zookeeper;

class Lock
{

    /** @var ZookeeperInterface */
    private $zk;

    /**
     * Lock constructor.
     * @param ZookeeperInterface $zk
     */
    public function __construct(ZookeeperInterface $zk)
    {
        $this->zk = $zk;
    }

}
