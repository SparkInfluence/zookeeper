<?php

namespace SparkInfluence\Zookeeper\Tests;

use Mockery;
use PHPUnit\Framework\TestCase;
use SparkInfluence\Zookeeper\Lock;
use SparkInfluence\Zookeeper\Zookeeper;

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

    public function testLockCatchesExceptions()
    {
        $zk = Mockery::mock(Zookeeper::class);
        $zk->shouldReceive('ensurePath')->andReturn(false);
        $lock = new Lock($zk);
        $this->assertNull($lock->lock('/lockTest2/noLock'));
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

    public function testNonLockNodesDoNotBlockLock()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest6/lock';
        $this->zookeeper->ensurePath("$toLock/1");
        $this->zookeeper->create("$toLock/lock-", 'Testing');
        $this->assertNotNull($lock->lock($toLock));
    }

    public function testIsLocked()
    {
        $lock = new Lock($this->zookeeper);
        $toLock = '/lockTest7/isLocked';
        $this->assertFalse($lock->isLocked($toLock));
        $lock->lock($toLock);
        $this->assertTrue($lock->isLocked($toLock));
    }

}
