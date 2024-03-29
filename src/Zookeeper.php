<?php

namespace SparkInfluence\Zookeeper;

use SparkInfluence\Zookeeper\Exception\{ConnectionError, Exception, NodeError};
use Throwable;
use Zookeeper as ZkExt;

class Zookeeper implements ZookeeperInterface
{

    /** @var ZkExt */
    private $zk;

    private static $instances = [];

    public function __construct(ZkExt $zk)
    {
        $this->zk = $zk;
    }

    /**
     * {@inheritdoc}
     * @throws Throwable
     */
    public function connect(string $zookeeperHost, callable $watcherCallback = null, int $timeout = 10000)
    {
        $zookeeperHost = trim($zookeeperHost, '/');
        $this->close();
        $counter = 0;
        $interval = 50; // Interval in milliseconds to check. Will double every time the connection couldn't be established
        $this->zk->connect($zookeeperHost, $watcherCallback, $timeout);
        do {
            if ($this->isConnected()) {
                break;
            }
            if ($counter === 6) {
                throw new ConnectionError('Could not connect to zookeeper server', $this->getState() ?: 255);
            }
            usleep($interval * 1000);
            $counter += 1;
            $interval *= 2;
        } while (true);
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function create(string $node, string $contents, ?int $flags = 0, ?array $acl = null): string
    {
        $node = $this->formatNodePath($node);
        try {
            $acl = $acl ?? [["perms" => ZkExt::PERM_ALL, "scheme" => "world", "id" => "anyone"]];
            $result = $this->zk->create($node, $contents, $acl, $flags);
        } catch (Throwable $e) {
            throw new Exception($e->getMessage(), $e->getCode(), $e);
        }
        return (string)$result;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function get(string $node, callable $watcherCallback = null, array &$stat = null, int $maxSize = 0): string
    {
        $node = $this->formatNodePath($node);
        $nodeContents = $this->zk->get($node, $watcherCallback, $stat, $maxSize);
        if ($nodeContents === false) {
            throw new NodeError(sprintf('Could not access node %s', $node), 1);
        }
        return (string)$nodeContents;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function set(string $node, string $data): bool
    {
        $node = $this->formatNodePath($node);
        $result = $this->zk->set($node, $data);
        return (bool)$result;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function getChildren(string $node, ?callable $watcherCallback = null): array
    {
        $node = $this->formatNodePath($node);
        $children = $this->zk->getChildren($node, $watcherCallback);
        if ($children === false) {
            throw new NodeError(sprintf('Could not list children of node %s', $node), 2);
        }
        return (array)$children;
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function exists(string $node, ?callable $watcherCallback = null): bool
    {
        $node = $this->formatNodePath($node);
        $exists = $this->zk->exists($node, $watcherCallback);
        return !empty($exists);
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function remove(string $node): bool
    {
        $node = $this->formatNodePath($node);
        $remove = $this->zk->delete($node);
        return (bool)$remove;
    }

    public function close()
    {
        try {
            $this->zk->close();
        } catch (Throwable $e) {
        }
    }

    public function ensurePath(string $node): bool
    {
        $parent = dirname('/' . trim($node, '/'));
        try {
            if ($this->exists($parent)) {
                return true;
            }
            if (!$this->ensurePath($parent)) {
                return false; // @codeCoverageIgnore
            }
            $this->create($parent, '');
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @throws Throwable
     */
    private function getState(): int
    {
        return (int)$this->zk->getState();
    }

    /**
     * @throws Throwable
     */
    private function isConnected(): bool
    {
        try {
            return $this->getState() === ZkExt::CONNECTED_STATE;
        } catch (Throwable $e) {
            return false;
        }
    }

    /**
     * @param string $node
     * @return string
     * @throws Exception
     */
    private function formatNodePath(string $node): string
    {
        $node = trim($node, '/');
        // regex to find invalid characters
        $pattern = '/' .
            '[' . // Start range match
            "\u{1}-\u{19}\u{7f}-\u{9f}" . // These don't display well, so zookeeper won't allow them
            "\u{d800}-\u{f8fff}\u{fff0}-\u{ffff}\u{f0000}-\u{fffff}" . // These following sequences are not allowed
            "\u{1fffe}-\u{1ffff}\u{2fffe}-\u{2ffff}\u{3fffe}-\u{3ffff}\u{4fffe}-\u{4ffff}" .
            "\u{5fffe}-\u{5ffff}\u{6fffe}-\u{6ffff}\u{7fffe}-\u{7ffff}\u{8fffe}-\u{8ffff}" .
            "\u{9fffe}-\u{9ffff}\u{afffe}-\u{affff}\u{bfffe}-\u{bffff}\u{cfffe}-\u{cffff}" .
            "\u{dfffe}-\u{dffff}\u{efffe}-\u{effff}" .
            "]" . // Close range match
            "/";
        $node = preg_replace($pattern, '', $node);
        $node = str_replace("\0", '', $node); // Now strip null bytes
        if (array_intersect(explode('/', trim($node, '/')), ['.', '..', 'zookeeper'])) {
            throw new Exception(sprintf('%s is an invalid path!', $node), 3);
        }
        return '/' . trim(preg_replace('@//+@', '/', $node), '/');
    }

    /**
     * Get or create a connection to zookeeper
     *
     * @param string $host
     * @param callable|null $callback
     * @param int $timeout
     * @return Zookeeper
     * @throws Throwable
     */
    public static function connection(string $host, ?callable $callback = null, int $timeout = 10000): self
    {
        $host = trim($host, '/');
        $instance = self::$instances[$host] ?? false;
        if (!$instance) {
            $zk = new ZkExt();
            $instance = new static($zk);
            self::$instances[$host] = $instance;
        }
        if (!$instance->isConnected()) {
            $instance->connect($host, $callback, $timeout);
        }
        return $instance;
    }

}
