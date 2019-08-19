<?php

namespace SparkInfluence\Zookeeper;

use SparkInfluence\Zookeeper\Exception\Exception;
use Throwable;
use Zookeeper as ZkExt;

class Lock
{

    /** @var ZookeeperInterface */
    private $zk;

    const TYPE_EXCLUSIVE = 'exclusive';
    const TYPE_READ = 'read';
    const TYPE_WRITE = 'write';

    /**
     * Lock constructor.
     * @param ZookeeperInterface $zk
     */
    public function __construct(ZookeeperInterface $zk)
    {
        $this->zk = $zk;
    }

    public function lock(string $key, int $timeout = 0): ?string
    {
        try {
            $full_key = $this->getLockName($key);
            $lock_key = $this->createLockKey($full_key);

            if (!$this->waitForLock($lock_key, $full_key, $timeout)) {
                // Clean up
                $this->zk->remove($lock_key);
                return null;
            }

            return $lock_key;
        } catch (Throwable $e) {
            return null;
        }
    }

    /**
     * @param string $key
     * @return string
     * @throws Exception
     */
    private function createLockKey(string $key): string
    {
        if (!$this->zk->ensurePath($key)) {
            throw new Exception('Could not create parent node!');
        }
        $flags = ZkExt::EPHEMERAL | ZkExt::SEQUENCE;
        return $this->zk->create($key, '1', $flags);
    }

    private function getLockName(string $key, string $type = 'exclusive'): string
    {
        switch ($type) {
            case self::TYPE_READ:
                $name = 'read-';
                break;
            case self::TYPE_WRITE:
            case self::TYPE_EXCLUSIVE:
            default:
                $name = 'lock-';
                break;
        }
        return $key . '/' . $name;
    }

    private function waitForLock(string $acquiredKey, string $baseKey, int $timeout): bool
    {
        $deadline = microtime(true) + $timeout;
        $acquiredIndex = $this->getIndex($acquiredKey);

        while (true) {
            if (!$this->isCurrentlyLocked($baseKey, $acquiredIndex)) {
                return true;
            }
            if ($deadline <= microtime(true)) {
                return false;
            }
            usleep(100000); // sleep for a tenth of a second
        }
        return false;
    }

    private function getIndex(string $key): ?int
    {
        if (!preg_match("/[0-9]+$/", $key, $matches)) {
            return null;
        }
        return intval(ltrim($matches[0], '0'));
    }

    private function isCurrentlyLocked(string $baseKey, ?int $indexFilter = null, ?string $nameFilter = null): bool
    {
        $parent = dirname($baseKey);
        if (!$this->zk->exists($parent)) {
            return false;
        }
        $children = $this->zk->getChildren($parent);
        foreach ($children as $childKey) {
            $child = "$parent/$childKey";
            if (!is_null($nameFilter) && strpos($child, $nameFilter) !== 0) {
                continue;
            }
            if (is_null($indexFilter)) {
                return true;
            }
            $child_index = $this->getIndex($childKey);
            if (is_null($child_index)) {
                // Not a sequence node
                continue;
            }
            if ($child_index < $indexFilter) {
                // smaller index
                return true;
            }
        }
        return false;
    }

}
