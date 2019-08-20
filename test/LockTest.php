<?php

namespace SparkInfluence\Zookeeper\Tests;

use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Lock;

class LockTest extends TestCase
{

    use ZkTrait;

    public function testLock()
    {
        $lock = new Lock($this->zookeeper);
        $this->assertNotNull($lock->lock('/lockTest1/xlock'));
    }

    public function testUnlock()
    {
        $lock = new Lock($this->zookeeper);
        $key = $lock->lock('/lockTest1/xlock2');
        $this->assertNotNull($key);
        $lock->unlock($key);
    }

    public function testLockBlocksLock()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest2/xlock';
        $key1 = $lock->lock($toLock);
        $this->assertNotNull($key1);
        $this->assertNull($lock->lock($toLock));
        $lock->unlock($key1);
        $this->assertNotNull($lock->lock($toLock));
    }

    public function testWriteLockBlocksLock()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest2/wlock';
        $key1 = $lock->writeLock($toLock);
        $this->assertNotNull($key1);
        $this->assertNull($lock->lock($toLock));
        $lock->unlock($key1);
    }

    public function testReadLockDoesNotBlockReadLock()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest3/rlock';
        $key1 = $lock->readLock($toLock);
        $key2 = $lock->readLock($toLock);
        $this->assertNotSame($key1, $key2);
        $this->assertNotNull($key1);
        $this->assertNotNull($key2);
    }

    public function testWriteLockBlocksReadLocks()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest4/rlock';
        $this->assertNotNull($lock->readLock($toLock));
        $this->assertNotNull($lock->readLock($toLock));
        $key3 = $lock->writeLock($toLock);
        $this->assertNotNull($key3);
        $this->assertNull($lock->readLock($toLock));
        $lock->unlock($key3);
        $this->assertNotNull($lock->readLock($toLock));
    }

    public function testLockTimeout()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest5/lock';
        $key1 = $lock->lock($toLock);
        $before = time();
        $this->assertNull($lock->lock($toLock, 2));
        $after = time();
        // Time should be at least two seconds past
        $this->assertGreaterThanOrEqual(2, $after - $before);
        $lock->unlock($key1);
        $lock->lock($toLock, 10);
        // Time should not be close to the ten second timeout
        $this->assertLessThanOrEqual(1, time() - $after);
    }

}
