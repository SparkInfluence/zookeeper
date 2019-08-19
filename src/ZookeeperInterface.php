<?php

namespace SparkInfluence\Zookeeper;

use SparkInfluence\Zookeeper\Exception\ConnectionError;

interface ZookeeperInterface
{

    /**
     * Synchronously connect to zookeeper
     *
     * @param string $zookeeperHost
     * @param callable $watcherCallback
     * @param int $timeout
     * @return void
     * @throws ConnectionError
     */
    public function connect(string $zookeeperHost, ?callable $watcherCallback = null, int $timeout = 10000);

    /**
     * @param string $node
     * @param string $contents
     * @param int $flags
     * @param array $acl
     * @return string|bool
     */
    public function create(string $node, string $contents, ?int $flags = null, ?array $acl = null): string;

    /**
     * @param string $node
     * @param callable $watcherCallback
     * @param array $stat
     * @param int $maxSize
     * @return string
     */
    public function get(string $node, callable $watcherCallback = null, array &$stat = null, int $maxSize = 0): string;

    /**
     * @param string $node
     * @param string $data
     * @return bool
     */
    public function set(string $node, string $data): bool;

    /**
     * @param string $node
     * @param callable $watcherCallback
     * @return bool
     */
    public function exists(string $node, ?callable $watcherCallback = null): bool;

    /**
     * @param string $node
     * @return bool
     */
    public function remove(string $node): bool;

    /**
     * @param string $node
     * @param callable $watcherCallback
     * @return string[]
     */
    public function getChildren(string $node, ?callable $watcherCallback = null): array;

    public function ensurePath(string $node): bool;

}
